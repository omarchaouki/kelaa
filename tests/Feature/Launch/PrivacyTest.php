<?php

use App\Actions\Account\DeleteAccount;
use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Jobs\ExportUserData;
use App\Livewire\Account\PrivacyPage;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\City;
use App\Models\Payment;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\User;
use App\Notifications\DataExportReady;
use App\Services\Ranking\RankingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->user = member(['name' => 'Salma', 'phone' => '+212612345678', 'phone_verified_at' => now(), 'email' => 'salma@example.test']);
    $this->business = Business::factory()->for(City::factory()->create(['slug' => 'ma-ville']))->create();
    $this->review = Review::factory()->for($this->business)->create(['user_id' => $this->user->id, 'rating' => 5, 'body' => 'Très bon accueil, travail soigné et rapide.']);
});

it('exports the user’s data as JSON without secrets', function () {
    Notification::fake();
    ReviewVote::query()->create(['review_id' => Review::factory()->for($this->business)->create()->id, 'user_id' => $this->user->id]);
    Payment::factory()->create(['user_id' => $this->user->id, 'business_id' => $this->business->id]);

    (new ExportUserData($this->user->id))->handle();

    $files = Storage::disk('local')->allFiles(ExportUserData::DIRECTORY.'/'.$this->user->id);
    expect($files)->toHaveCount(1);

    $raw = Storage::disk('local')->get($files[0]);
    $data = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

    expect($data['account'])->toMatchArray(['name' => 'Salma', 'phone' => '+212612345678', 'email' => 'salma@example.test'])
        ->and($data['account']['roles'])->toBe(['member'])
        ->and($data['reviews'][0])->toMatchArray(['business' => $this->business->name, 'rating' => 5, 'body' => 'Très bon accueil, travail soigné et rapide.'])
        ->and($data['helpful_votes'])->toHaveCount(1)
        ->and($data['payments'])->toHaveCount(1)
        ->and(array_keys($data))->toContain('replies', 'favorites', 'reports', 'claims', 'edit_suggestions', 'businesses_owned', 'notifications')
        ->and($data['account'])->not->toHaveKeys(['password', 'remember_token', 'google_id'])
        ->and($raw)->not->toContain('remember_token');

    Notification::assertSentTo($this->user, DataExportReady::class);
});

it('lets only the same logged-in user download the export, through a signed link', function () {
    Notification::fake();
    (new ExportUserData($this->user->id))->handle();

    $url = null;
    Notification::assertSentTo($this->user, DataExportReady::class, function (DataExportReady $notification) use (&$url) {
        $url = $notification->url($this->user);

        return true;
    });

    $this->actingAs($this->user)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');

    // Another user with the same link, a guest, or an unsigned URL.
    $this->actingAs(member())->get($url)->assertNotFound();
    auth()->logout();
    $this->get($url)->assertRedirect();
    $this->actingAs($this->user)->get(strtok($url, '?'))->assertForbidden();

    // The link expires.
    $this->travel(ExportUserData::LIFETIME_DAYS + 1)->days();
    $this->actingAs($this->user)->get($url)->assertForbidden();
});

it('queues one export per day from the privacy page', function () {
    Queue::fake();

    Livewire::actingAs($this->user)->test(PrivacyPage::class)
        ->call('requestExport')->assertHasNoErrors()
        ->call('requestExport')->assertHasErrors('export');

    Queue::assertPushed(ExportUserData::class, 1);
});

it('prunes old exports', function () {
    Notification::fake();
    (new ExportUserData($this->user->id))->handle();

    $this->artisan('privacy:prune-exports')->assertSuccessful();
    expect(Storage::disk('local')->allFiles(ExportUserData::DIRECTORY))->toHaveCount(1);

    $this->travel(ExportUserData::LIFETIME_DAYS + 1)->days();
    $this->artisan('privacy:prune-exports')->assertSuccessful();
    expect(Storage::disk('local')->allFiles(ExportUserData::DIRECTORY))->toHaveCount(0);
});

it('deletes the account but keeps the reviews signed "Utilisateur supprimé"', function () {
    $this->business->forceFill(['owner_id' => $this->user->id, 'is_claimed' => true])->save();
    $claim = BusinessClaim::factory()->create(['user_id' => $this->user->id, 'business_id' => $this->business->id]);
    $claim->addMedia(UploadedFile::fake()->createWithContent('rc.pdf', '%PDF-1.4
%test'))->toMediaCollection('documents');
    $documentPath = $claim->getFirstMedia('documents')->getPathRelativeToRoot();
    $payment = Payment::factory()->create(['user_id' => $this->user->id, 'business_id' => $this->business->id]);

    app(DeleteAccount::class)($this->user);

    expect(User::query()->find($this->user->id))->toBeNull()
        ->and($this->review->fresh())->not->toBeNull()
        ->and($this->review->fresh()->user_id)->toBeNull()
        ->and($this->review->fresh()->authorName())->toBe('Utilisateur supprimé')
        ->and($this->business->fresh()->owner_id)->toBeNull()
        ->and($this->business->fresh()->is_claimed)->toBeFalse()
        ->and(BusinessClaim::query()->find($claim->id))->toBeNull()
        ->and(Storage::disk('local')->exists($documentPath))->toBeFalse()
        ->and($payment->fresh()->user_id)->toBeNull();  // invoices and payments are kept for accounting

    $this->get('/fr/ma-ville/b/'.$this->business->slug)->assertOk()->assertSee('Utilisateur supprimé')->assertDontSee('Salma');
});

it('removes the reviews too when asked, and recomputes the rating', function () {
    app(RankingService::class)->recalculate($this->business);
    expect($this->business->fresh()->rating_count)->toBe(1);

    app(DeleteAccount::class)($this->user, removeReviews: true);

    expect(Review::query()->find($this->review->id))->toBeNull()
        ->and($this->business->fresh()->rating_count)->toBe(0);
});

it('requires a code sent to the verified phone to delete from the page', function () {
    $sender = fakeOtpSender();

    $page = Livewire::actingAs($this->user)->test(PrivacyPage::class)
        ->call('confirmDeletion')->assertHasErrors('understood')
        ->set('understood', true)
        ->call('startDeletion')->assertSet('codeSent', true);

    expect($sender->sent[0]['purpose'])->toBe(OtpPurpose::AccountDeletion);

    $page->set('code', '000000')->call('confirmDeletion')->assertHasErrors('code');
    expect(User::query()->find($this->user->id))->not->toBeNull();

    $page->set('code', $sender->lastCodeFor('+212612345678'))->call('confirmDeletion')->assertRedirect(route('locale.home'));

    expect(User::query()->find($this->user->id))->toBeNull()
        ->and(auth()->check())->toBeFalse();
});

it('cannot skip the code by calling the confirmation directly', function () {
    fakeOtpSender();

    Livewire::actingAs($this->user)->test(PrivacyPage::class)
        ->set('understood', true)
        ->set('code', '123456')
        ->call('confirmDeletion');

    expect(User::query()->find($this->user->id))->not->toBeNull();
});

it('asks accounts without a phone for a recent sign-in', function () {
    $google = member(['phone' => null, 'phone_verified_at' => null, 'last_login_at' => now()->subHour()]);

    Livewire::actingAs($google)->test(PrivacyPage::class)
        ->set('understood', true)
        ->call('confirmDeletion')
        ->assertHasErrors('understood');

    expect(User::query()->find($google->id))->not->toBeNull();

    $google->forceFill(['last_login_at' => now()])->save();

    Livewire::actingAs($google)->test(PrivacyPage::class)
        ->set('understood', true)
        ->call('confirmDeletion')
        ->assertRedirect();

    expect(User::query()->find($google->id))->toBeNull();
});

it('never lets staff delete their own account', function () {
    app(DeleteAccount::class)(staff(Role::Admin));
})->throws(AuthorizationException::class);

it('opens the privacy page only when logged in', function () {
    $this->get('/fr/account/donnees')->assertRedirect();
    $this->actingAs($this->user)->get('/fr/account/donnees')->assertOk()->assertSee('Mes données')->assertSee('Supprimer mon compte');
});
