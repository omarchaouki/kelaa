<?php

use App\Enums\Role;
use App\Livewire\Account\VerifyPhone;
use App\Livewire\Auth\PasswordLogin;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    config(['auth_options.sms_verification' => false]);
    City::factory()->create(['slug' => config('directory.default_city')]);
});

it('is not available while SMS verification is on', function () {
    config(['auth_options.sms_verification' => true]);

    Livewire::test(PasswordLogin::class)->assertNotFound();
});

it('creates a member account with phone and password', function () {
    Livewire::test(PasswordLogin::class)
        ->call('switchMode', 'register')
        ->set('phone', '06 12 34 56 78')
        ->set('password', 'secret123')
        ->set('password_confirmation', 'secret123')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect();

    $user = User::query()->where('phone', '+212612345678')->sole();

    expect(Auth::id())->toBe($user->id)
        ->and($user->hasRole(Role::Member->value))->toBeTrue()
        ->and(Hash::check('secret123', $user->password))->toBeTrue()
        ->and($user->phone_verified_at)->toBeNull()
        ->and($user->hasVerifiedPhone())->toBeTrue();
});

it('rejects weak passwords, mismatches and landlines on sign-up', function (string $phone, string $password, string $confirmation, string $field) {
    Livewire::test(PasswordLogin::class)
        ->call('switchMode', 'register')
        ->set('phone', $phone)
        ->set('password', $password)
        ->set('password_confirmation', $confirmation)
        ->call('register')
        ->assertHasErrors($field);

    expect(User::query()->count())->toBe(0);
})->with([
    'too short' => ['0612345678', 'abc12', 'abc12', 'password'],
    'no digit' => ['0612345678', 'abcdefghij', 'abcdefghij', 'password'],
    'mismatch' => ['0612345678', 'secret123', 'secret124', 'password'],
    'landline' => ['0522123456', 'secret123', 'secret123', 'phone'],
]);

it('refuses a second account on the same number', function () {
    User::factory()->create(['phone' => '+212612345678', 'password' => 'secret123']);

    Livewire::test(PasswordLogin::class)
        ->call('switchMode', 'register')
        ->set('phone', '0612345678')
        ->set('password', 'other1234')
        ->set('password_confirmation', 'other1234')
        ->call('register')
        ->assertHasErrors('phone');

    expect(User::query()->count())->toBe(1)->and(Auth::check())->toBeFalse();
});

it('logs in with the right password only', function () {
    $user = User::factory()->create(['phone' => '+212612345678', 'password' => 'secret123']);

    Livewire::test(PasswordLogin::class)
        ->set('phone', '0612345678')
        ->set('password', 'wrong-pass1')
        ->call('login')
        ->assertHasErrors('phone');

    expect(Auth::check())->toBeFalse();

    Livewire::test(PasswordLogin::class)
        ->set('phone', '06 12 34 56 78')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect(Auth::id())->toBe($user->id);
});

it('gives the same error for an unknown number', function () {
    Livewire::test(PasswordLogin::class)
        ->set('phone', '0612345678')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasErrors(['phone' => __('Numéro ou mot de passe incorrect.')]);
});

it('locks a number after 5 wrong passwords', function () {
    User::factory()->create(['phone' => '+212612345678', 'password' => 'secret123']);

    foreach (range(1, 5) as $attempt) {
        Livewire::test(PasswordLogin::class)->set('phone', '0612345678')->set('password', "wrong{$attempt}x")->call('login');
    }

    Livewire::test(PasswordLogin::class)
        ->set('phone', '0612345678')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasErrors('phone');

    expect(Auth::check())->toBeFalse();
});

it('does not log in banned accounts', function () {
    User::factory()->create(['phone' => '+212612345678', 'password' => 'secret123', 'banned_at' => now()]);

    Livewire::test(PasswordLogin::class)
        ->set('phone', '0612345678')
        ->set('password', 'secret123')
        ->call('login')
        ->assertHasErrors('phone');

    expect(Auth::check())->toBeFalse();
});

it('lets an account without a number save one without a code', function () {
    $user = User::factory()->create(['phone' => null, 'phone_verified_at' => null]);

    Livewire::actingAs($user)->test(VerifyPhone::class)
        ->set('phone', '0612345678')
        ->call('sendCode')
        ->assertHasNoErrors()
        ->assertRedirect();

    expect($user->fresh()->phone)->toBe('+212612345678')
        ->and($user->fresh()->hasVerifiedPhone())->toBeTrue();
});
