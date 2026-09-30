<?php

use App\Actions\Auth\ResolveUserForGoogleAccount;
use App\Enums\Role;
use App\Livewire\Auth\EmailLogin;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;
use Livewire\Livewire;

function googleUser(string $id, ?string $email, bool $verified = true): SocialiteUser
{
    return (new SocialiteUser)
        ->setRaw(['email_verified' => $verified])
        ->map(['id' => $id, 'name' => 'Salma Test', 'email' => $email]);
}

it('hides email and Google login when they are disabled', function () {
    config(['auth_options.email_password' => false, 'auth_options.google' => false]);

    $this->get('/fr/login/email')->assertNotFound();
    $this->get('/fr/login/google')->assertNotFound();
    $this->get('/fr/login')->assertDontSee('Continuer avec Google')->assertDontSee('Se connecter avec un e-mail');
});

it('logs in with email and password when enabled', function () {
    config(['auth_options.email_password' => true]);
    $user = User::factory()->withEmail()->create(['email' => 'owner@example.test']);

    Livewire::test(EmailLogin::class)
        ->set('email', 'owner@example.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('refuses wrong passwords and banned accounts', function () {
    config(['auth_options.email_password' => true]);
    User::factory()->withEmail()->create(['email' => 'owner@example.test']);
    User::factory()->withEmail()->banned()->create(['email' => 'banned@example.test']);

    Livewire::test(EmailLogin::class)
        ->set('email', 'owner@example.test')->set('password', 'wrong')
        ->call('login')->assertHasErrors('email');

    Livewire::test(EmailLogin::class)
        ->set('email', 'banned@example.test')->set('password', 'password')
        ->call('login')->assertHasErrors('email');

    $this->assertGuest();
});

it('creates a member account from a new Google identity', function () {
    $user = app(ResolveUserForGoogleAccount::class)(googleUser('g-1', 'new@example.test'), 'ar');

    expect($user->google_id)->toBe('g-1')
        ->and($user->locale)->toBe('ar')
        ->and($user->hasRole(Role::Member->value))->toBeTrue()
        ->and($user->hasVerifiedPhone())->toBeFalse();
});

it('links a Google identity to an account with the same verified email', function () {
    $existing = User::factory()->withEmail()->create(['email' => 'same@example.test']);

    $user = app(ResolveUserForGoogleAccount::class)(googleUser('g-2', 'same@example.test'), 'fr');

    expect($user->is($existing))->toBeTrue()
        ->and($user->google_id)->toBe('g-2');
});

it('never links accounts on an unverified Google email', function () {
    $existing = User::factory()->withEmail()->create(['email' => 'victim@example.test']);

    $user = app(ResolveUserForGoogleAccount::class)(googleUser('g-3', 'victim@example.test', verified: false), 'fr');

    expect($user->is($existing))->toBeFalse()
        ->and($existing->fresh()->google_id)->toBeNull();
});
