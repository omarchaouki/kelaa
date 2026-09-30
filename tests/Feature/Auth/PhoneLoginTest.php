<?php

use App\Enums\Role;
use App\Livewire\Auth\PhoneLogin;
use App\Models\City;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    City::factory()->create(['slug' => config('directory.default_city')]);
    $this->sender = fakeOtpSender();
});

it('shows the login page to guests in every locale', function (string $locale) {
    $this->get("/{$locale}/login")->assertOk()->assertSeeLivewire(PhoneLogin::class);
})->with(['fr', 'ar', 'en']);

it('redirects logged-in users away from the login page', function () {
    $this->actingAs(member())->get('/fr/login')->assertRedirect();
});

it('validates the phone number', function () {
    Livewire::test(PhoneLogin::class)
        ->set('phone', '0522123456')
        ->call('sendCode')
        ->assertHasErrors('phone')
        ->assertSet('normalizedPhone', null);

    expect($this->sender->sent)->toBeEmpty();
});

it('creates a member account with a verified phone on first login', function () {
    $component = Livewire::test(PhoneLogin::class)
        ->set('phone', '06 12 34 56 78')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertSet('normalizedPhone', '+212612345678');

    $component->set('code', $this->sender->lastCodeFor('+212612345678'))
        ->call('verify')
        ->assertHasNoErrors()
        ->assertRedirect();

    $user = User::query()->where('phone', '+212612345678')->sole();

    expect($user->hasVerifiedPhone())->toBeTrue()
        ->and($user->hasRole(Role::Member->value))->toBeTrue()
        ->and($user->last_login_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('logs an existing user in without creating a new account', function () {
    $existing = member(['phone' => '+212712345678']);

    Livewire::test(PhoneLogin::class)
        ->set('phone', '0712345678')
        ->call('sendCode')
        ->set('code', $this->sender->lastCodeFor('+212712345678'))
        ->call('verify');

    expect(User::query()->count())->toBe(1);
    $this->assertAuthenticatedAs($existing);
});

it('refuses a wrong code', function () {
    Livewire::test(PhoneLogin::class)
        ->set('phone', '0612345678')
        ->call('sendCode')
        ->set('code', $this->sender->lastCodeFor('+212612345678') === '000000' ? '111111' : '000000')
        ->call('verify')
        ->assertHasErrors('code');

    $this->assertGuest();
});

it('refuses banned accounts', function () {
    User::factory()->banned()->create(['phone' => '+212612345678']);

    Livewire::test(PhoneLogin::class)
        ->set('phone', '0612345678')
        ->call('sendCode')
        ->set('code', $this->sender->lastCodeFor('+212612345678'))
        ->call('verify')
        ->assertHasErrors('code');

    $this->assertGuest();
});

it('shows a throttling message instead of sending too many codes', function () {
    config(['otp.rate_limits.per_phone' => ['attempts' => 1, 'decay_minutes' => 15]]);

    Livewire::test(PhoneLogin::class)
        ->set('phone', '0612345678')
        ->call('sendCode')
        ->call('changeNumber')
        ->set('phone', '0612345678')
        ->call('sendCode')
        ->assertHasErrors('phone');

    expect($this->sender->sent)->toHaveCount(1);
});

it('logs out', function () {
    $this->actingAs(member())->post('/fr/logout')->assertRedirect('/fr');

    $this->assertGuest();
});
