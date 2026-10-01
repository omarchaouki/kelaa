<?php

use App\Actions\Moderation\ModerateClaim;
use App\Actions\Owners\ApproveClaim;
use App\Actions\Owners\ClaimBusiness;
use App\Enums\ClaimMethod;
use App\Enums\ClaimStatus;
use App\Enums\Role;
use App\Exceptions\UnauthorizedStatusChange;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\City;
use App\Notifications\ClaimDecided;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->sender = fakeOtpSender();
    $this->business = Business::factory()->for(City::factory()->create(['slug' => 'ma-ville']))->create(['whatsapp' => '0611223344', 'phone' => '0522000000']);
    $this->claimant = member();
    $this->claims = app(ClaimBusiness::class);
});

it('approves a claim when the code sent to the business number is confirmed', function () {
    Notification::fake();

    $claim = $this->claims->startPhoneClaim($this->claimant, $this->business, '1.2.3.4');
    $code = $this->sender->lastCodeFor('+212611223344');

    expect($this->sender->sent[0]['phone'])->toBe('+212611223344')   // the listing's number, not the claimant's
        ->and($this->claims->verifyPhoneClaim($this->claimant, $claim, $code))->toBeTrue();

    $this->business->refresh();
    expect($this->business->owner_id)->toBe($this->claimant->id)
        ->and($this->business->is_claimed)->toBeTrue()
        ->and($this->claimant->fresh()->hasRole(Role::BusinessOwner->value))->toBeTrue()
        ->and($claim->fresh()->status)->toBe(ClaimStatus::Approved);
    Notification::assertSentTo($this->claimant, ClaimDecided::class);
});

it('rejects a wrong code', function () {
    $claim = $this->claims->startPhoneClaim($this->claimant, $this->business, '1.2.3.4');

    expect($this->claims->verifyPhoneClaim($this->claimant, $claim, $this->sender->lastCodeFor('+212611223344') === '000000' ? '111111' : '000000'))->toBeFalse()
        ->and($this->business->fresh()->owner_id)->toBeNull();
});

it('does not let another user use someone else’s claim', function () {
    $claim = $this->claims->startPhoneClaim($this->claimant, $this->business, '1.2.3.4');
    $code = $this->sender->lastCodeFor('+212611223344');

    $this->claims->verifyPhoneClaim(member(), $claim, $code);
})->throws(AuthorizationException::class);

it('refuses claims on listings that already have an owner', function () {
    $this->business->forceFill(['owner_id' => member()->id, 'is_claimed' => true])->save();

    $this->claims->startPhoneClaim($this->claimant, $this->business, '1.2.3.4');
})->throws(AuthorizationException::class);

it('stores claim documents privately and lets moderators decide', function () {
    $pdf = UploadedFile::fake()->createWithContent('rc.pdf', "%PDF-1.4\n%fake registre de commerce\n");

    $claim = $this->claims->submitDocuments($this->claimant, $this->business, [$pdf, UploadedFile::fake()->image('cin.jpg')], 'Je suis le gérant');
    $media = $claim->getMedia('documents');

    expect($claim->method)->toBe(ClaimMethod::DocumentUpload)
        ->and($claim->status)->toBe(ClaimStatus::Pending)
        ->and($media)->toHaveCount(2)
        ->and($media->pluck('disk')->unique()->all())->toBe(['local'])
        ->and($media->first()->getUrl())->not->toContain('rc.pdf');

    app(ModerateClaim::class)->approve(staff(Role::Moderator), $claim);

    expect($this->business->fresh()->owner_id)->toBe($this->claimant->id);
});

it('refuses fake PDFs and non-image files', function () {
    $fake = UploadedFile::fake()->createWithContent('rc.pdf', '<html><script>alert(1)</script></html>');

    $this->claims->submitDocuments($this->claimant, $this->business, [$fake], null);
})->throws(InvalidArgumentException::class);

it('serves claim documents only to moderators, sandboxed', function () {
    $claim = $this->claims->submitDocuments($this->claimant, $this->business, [UploadedFile::fake()->createWithContent('rc.pdf', "%PDF-1.4\nok\n")], null);
    $media = $claim->getFirstMedia('documents');

    $this->actingAs($this->claimant)->get(route('admin.private-media', $media))->assertNotFound();
    $this->actingAs(staff(Role::BusinessOwner))->get(route('admin.private-media', $media))->assertNotFound();

    $response = $this->actingAs(staff(Role::Moderator))->get(route('admin.private-media', $media))->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment')
        ->and($response->headers->get('Content-Security-Policy'))->toContain('sandbox')
        ->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('closes competing claims when one is approved', function () {
    $other = member();
    $first = $this->claims->submitDocuments($this->claimant, $this->business, [UploadedFile::fake()->image('a.jpg')], null);
    $second = $this->claims->submitDocuments($other, $this->business, [UploadedFile::fake()->image('b.jpg')], null);

    app(ModerateClaim::class)->approve(staff(Role::Admin), $first);

    expect($second->fresh()->status)->toBe(ClaimStatus::Rejected);
});

it('cannot approve a claim outside moderation or a verified OTP', function () {
    $claim = BusinessClaim::factory()->create(['business_id' => $this->business->id]);

    app(ApproveClaim::class)($claim, null);
})->throws(UnauthorizedStatusChange::class);

it('only lets moderators decide document claims', function () {
    $claim = BusinessClaim::factory()->create(['business_id' => $this->business->id]);

    app(ModerateClaim::class)->approve($this->claimant, $claim);
})->throws(AuthorizationException::class);

it('shows the claim page and the OTP option', function () {
    $this->actingAs($this->claimant)
        ->get('/fr/ma-ville/b/'.$this->business->slug.'/revendiquer')
        ->assertOk()
        ->assertSee('06 11 •• •• 44')
        ->assertDontSee('+212611223344');
});
