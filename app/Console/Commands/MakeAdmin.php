<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Grants a staff role to an account. The admin panel login uses email +
 * password, so an email and password are set here as well.
 */
#[Signature('app:make-admin {phone : Moroccan mobile number} {--email= : Email used to log in to /admin} {--role=admin : admin or moderator}')]
#[Description('Create or promote a staff account (admin or moderator)')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $phone = MoroccanPhone::normalize((string) $this->argument('phone'));
        $role = Role::tryFrom((string) $this->option('role'));

        if ($phone === null) {
            $this->error('Invalid Moroccan mobile number.');

            return self::FAILURE;
        }

        if (! in_array($role, [Role::Admin, Role::Moderator], true)) {
            $this->error('Role must be "admin" or "moderator".');

            return self::FAILURE;
        }

        $email = $this->option('email') ?: $this->ask('Email for the admin panel login');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email.');

            return self::FAILURE;
        }

        $password = $this->secret('Password (min. 12 characters)');

        if (! is_string($password) || mb_strlen($password) < 12) {
            $this->error('The password must contain at least 12 characters.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['phone' => $phone]);
        $user->forceFill([
            'email' => $email,
            'password' => $password,
            'phone_verified_at' => $user->phone_verified_at ?? now(),
            'email_verified_at' => now(),
        ])->save();

        $user->syncRoles([Role::Member->value, $role->value]);

        $this->info("{$phone} is now {$role->value}.");

        return self::SUCCESS;
    }
}
