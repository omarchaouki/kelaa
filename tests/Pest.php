<?php

use App\Contracts\OtpSender;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeOtpSender;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Unit tests are plain PHP (no framework, no database). Feature tests boot
| the application on a fresh database with roles and permissions seeded.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function fakeOtpSender(): FakeOtpSender
{
    $fake = new FakeOtpSender;
    app()->instance(OtpSender::class, $fake);

    return $fake;
}

function member(array $attributes = []): User
{
    return User::factory()->withRole(Role::Member)->create($attributes);
}

function staff(Role $role, array $attributes = []): User
{
    return User::factory()->withRole($role)->create($attributes);
}
