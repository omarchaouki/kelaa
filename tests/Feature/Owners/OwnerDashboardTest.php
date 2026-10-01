<?php

use App\Actions\Moderation\ModerateEditSuggestion;
use App\Actions\Owners\SuggestEdit;
use App\Actions\Owners\UpdateBusinessProfile;
use App\Enums\BusinessStatus;
use App\Enums\ContactEventSource;
use App\Enums\ContactEventType;
use App\Enums\EditSuggestionStatus;
use App\Enums\Role;
use App\Livewire\Owners\BusinessDashboard;
use App\Models\Business;
use App\Models\City;
use App\Models\ContactEvent;
use App\Models\Plan;
use App\Models\Review;
use App\Services\Owners\OwnerStatistics;
use App\Services\Owners\ProfileCompleteness;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for(City::factory()->create(['slug' => 'ma-ville']))->create(['owner_id' => $this->owner->id, 'is_claimed' => true]);
    Plan::factory()->free()->create(['slug' => 'free']);
});

function paidPlan(Business $business, array $features = []): void
{
    $plan = Plan::factory()->create(['features' => $features + ['max_photos' => 15, 'show_stats' => true, 'logo_and_cover' => true, 'highlighted_card' => true, 'remove_competitor_ads' => false, 'priority_support' => false]]);
    $business->forceFill(['plan_id' => $plan->id, 'plan_expires_at' => now()->addMonth()])->save();
}

it('opens the dashboard only to the owner of the listing', function () {
    $this->actingAs($this->owner)->get('/fr/dashboard/'.$this->business->id)->assertOk();
    $this->actingAs(staff(Role::BusinessOwner))->get('/fr/dashboard/'.$this->business->id)->assertForbidden();
    $this->actingAs(member())->get('/fr/dashboard/'.$this->business->id)->assertForbidden();
});

it('sends guests to the login page', function () {
    $this->get('/fr/dashboard/'.$this->business->id)->assertRedirect('/fr/login');
});

it('locks the dashboard of a suspended listing', function () {
    $this->business->forceFill(['status' => BusinessStatus::Suspended])->save();

    $this->actingAs($this->owner)->get('/fr/dashboard/'.$this->business->id)->assertForbidden();
});

it('lets owners edit description, links and hours directly', function () {
    Livewire::actingAs($this->owner)
        ->test(BusinessDashboard::class, ['business' => $this->business])
        ->set('description.fr', 'Plombier depuis 20 ans, intervention rapide dans toute la ville.')
        ->set('website', 'atlas-plomberie.ma')
        ->call('saveInfo')
        ->assertHasNoErrors()
        ->set('week.1.closed', false)
        ->set('week.1.slots', [['08:00', '12:00'], ['14:00', '19:00']])
        ->call('saveHours')
        ->assertHasNoErrors();

    $this->business->refresh();
    expect($this->business->website)->toBe('https://atlas-plomberie.ma')
        ->and($this->business->getTranslation('description', 'fr'))->toStartWith('Plombier depuis')
        ->and($this->business->hours()->where('day_of_week', 1)->count())->toBe(2);
});

it('refuses unsafe links from the dashboard', function () {
    Livewire::actingAs($this->owner)
        ->test(BusinessDashboard::class, ['business' => $this->business])
        ->set('website', 'javascript:alert(document.cookie)')
        ->set('social.facebook', 'data:text/html,<script>alert(1)</script>')
        ->call('saveInfo')
        ->assertHasErrors(['website', 'social.facebook']);
});

it('cannot touch another owner’s listing through the actions', function () {
    $other = Business::factory()->create(['owner_id' => staff(Role::BusinessOwner)->id]);

    app(UpdateBusinessProfile::class)->details($this->owner, $other, ['website' => 'https://evil.example']);
})->throws(AuthorizationException::class);

it('routes identity changes by the owner through moderation', function () {
    $suggestion = app(SuggestEdit::class)($this->owner, $this->business, ['name' => 'Nouveau nom', 'whatsapp' => '0677777777']);

    expect($suggestion->from_owner)->toBeTrue()
        ->and($suggestion->status)->toBe(EditSuggestionStatus::Pending)
        ->and($this->business->fresh()->name)->not->toBe('Nouveau nom');

    app(ModerateEditSuggestion::class)->apply(staff(Role::Moderator), $suggestion);

    expect($this->business->fresh()->name)->toBe('Nouveau nom')
        ->and($this->business->fresh()->whatsapp)->toBe('+212677777777');
});

it('ignores fields outside the suggestion whitelist', function () {
    $suggestion = app(SuggestEdit::class)(member(), $this->business, [
        'address' => 'Nouvelle adresse 5',
        'owner_id' => 999,
        'status' => 'published',
        'bayesian_score' => 5,
        'is_verified' => true,
    ]);

    expect(array_keys($suggestion->changes))->toBe(['address']);
});

it('caps photos at the plan limit and keeps extra photos hidden after a downgrade', function () {
    $update = app(UpdateBusinessProfile::class);
    $photos = fn (int $n) => array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg", 400, 300), range(1, $n));

    expect($update->addPhotos($this->owner, $this->business, $photos(5)))->toBe(3);   // free plan: 3

    paidPlan($this->business);
    expect($update->addPhotos($this->owner, $this->business->fresh(), $photos(4)))->toBe(4)
        ->and($this->business->fresh()->getMedia('gallery'))->toHaveCount(7);

    // The paid plan expires: photos are kept, the public page shows only 3.
    $this->business->forceFill(['plan_expires_at' => now()->subDay()])->save();
    $html = $this->get(route('city.business', ['locale' => 'fr', 'city' => $this->business->city, 'business' => $this->business]))->getContent();

    expect($this->business->fresh()->getMedia('gallery'))->toHaveCount(7)
        ->and(substr_count($html, 'Photo de '))->toBe(3);
});

it('reserves the logo for paid plans', function () {
    app(UpdateBusinessProfile::class)->addPhotos($this->owner, $this->business, [UploadedFile::fake()->image('logo.png')], 'logo');
})->throws(AuthorizationException::class);

it('shows statistics only on paid plans', function () {
    $this->actingAs($this->owner)->get('/fr/dashboard/'.$this->business->id.'?tab=stats')->assertSee('Les statistiques font partie des formules payantes.');

    paidPlan($this->business);
    ContactEvent::factory()->count(3)->create(['business_id' => $this->business->id, 'type' => ContactEventType::WhatsappClick, 'source' => ContactEventSource::Organic, 'created_at' => now()]);

    $summary = app(OwnerStatistics::class)->summary($this->business->fresh(), '7d');
    expect($summary['totals']['whatsapp_click'])->toBe(3)
        ->and($summary['contacts'])->toBe(3)
        ->and($summary['daily'])->toHaveCount(7);

    $this->actingAs($this->owner)->get('/fr/dashboard/'.$this->business->id.'?tab=stats')->assertOk()->assertDontSee('Les statistiques font partie des formules payantes.');
});

it('lets the owner reply from the dashboard but never edit the review', function () {
    $review = Review::factory()->for($this->business)->create(['body' => 'Très bon accueil.']);

    Livewire::actingAs($this->owner)
        ->test(BusinessDashboard::class, ['business' => $this->business, 'tab' => 'reviews'])
        ->set("replyBodies.{$review->id}", 'Merci beaucoup !')
        ->call('saveReply', $review->id)
        ->assertHasNoErrors();

    expect($review->fresh()->body)->toBe('Très bon accueil.')
        ->and($review->reply()->sole()->body)->toBe('Merci beaucoup !');
});

it('scores profile completeness', function () {
    $empty = Business::factory()->create(['description' => null, 'phone' => null, 'whatsapp' => null, 'address' => null, 'price_range' => null, 'website' => null, 'neighborhood_id' => null]);

    expect(app(ProfileCompleteness::class)->score($empty))->toBe(0)
        ->and($empty->fresh()->profile_completeness)->toBe(0);

    $empty->update(['whatsapp' => '0612345678', 'address' => 'Rue 1']);
    expect($empty->fresh()->profile_completeness)->toBe(25);
});
