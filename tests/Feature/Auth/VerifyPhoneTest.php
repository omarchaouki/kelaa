<?php

use App\Livewire\Account\VerifyPhone;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->sender = fakeOtpSender();
});

it('requires authentication', function () {
    $this->get('/fr/account/phone')->assertRedirect('/fr/login');
});

it('adds a verified phone to an account created without one', function () {
    $user = User::factory()->create(['phone' => null, 'phone_verified_at' => null, 'email' => 'someone@example.test']);

    Livewire::actingAs($user)
        ->test(VerifyPhone::class)
        ->set('phone', '0698765432')
        ->call('sendCode')
        ->set('code', $this->sender->lastCodeFor('+212698765432'))
        ->call('verify')
        ->assertHasNoErrors();

    expect($user->fresh()->phone)->toBe('+212698765432')
        ->and($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

it('refuses a phone number that belongs to another account', function () {
    member(['phone' => '+212698765432']);
    $user = User::factory()->create(['phone' => null, 'phone_verified_at' => null]);

    Livewire::actingAs($user)
        ->test(VerifyPhone::class)
        ->set('phone', '0698765432')
        ->call('sendCode')
        ->assertHasErrors('phone');

    expect($this->sender->sent)->toBeEmpty();
});
