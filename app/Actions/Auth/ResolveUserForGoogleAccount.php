<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

/**
 * Finds the account linked to a Google identity, links it to an existing
 * account with the same (Google-verified) email, or creates a member account.
 */
class ResolveUserForGoogleAccount
{
    public function __invoke(SocialiteUser $googleUser, string $locale): User
    {
        return DB::transaction(function () use ($googleUser, $locale): User {
            $user = User::query()->where('google_id', $googleUser->getId())->first();

            // Only trust the email for account linking when Google verified it.
            $raw = method_exists($googleUser, 'getRaw') ? $googleUser->getRaw() : [];
            $email = ($raw['email_verified'] ?? false) ? $googleUser->getEmail() : null;

            if ($user === null && $email !== null) {
                $user = User::query()->where('email', $email)->first();
            }

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'locale' => $locale,
                ]);
                $user->assignRole(Role::Member->value);
            }

            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'email_verified_at' => $user->email_verified_at ?? ($email !== null ? now() : null),
                'last_login_at' => now(),
            ])->save();

            return $user;
        });
    }
}
