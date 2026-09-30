<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Called once an OTP for a phone number has been verified: returns the
 * matching account, creating a member account on first login.
 */
class ResolveUserForVerifiedPhone
{
    public function __invoke(string $phone, string $locale): User
    {
        return DB::transaction(function () use ($phone, $locale): User {
            $user = User::query()->where('phone', $phone)->lockForUpdate()->first();

            if ($user === null) {
                $user = User::query()->create(['phone' => $phone, 'locale' => $locale]);
                $user->assignRole(Role::Member->value);
            }

            $user->forceFill([
                'phone_verified_at' => $user->phone_verified_at ?? now(),
                'last_login_at' => now(),
            ])->save();

            return $user;
        });
    }
}
