<?php

namespace App\Support\Billing;

use App\Enums\Permission;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * The only way to change the status of a payment (and what it activates).
 *
 * - asAdmin(): an admin holding "manage payments" (moderators never do).
 * - asSystem(): scheduled jobs (expiry of unpaid orders) and verified
 *   gateway callbacks.
 */
final class BillingGate
{
    private static ?User $admin = null;

    private static bool $system = false;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws AuthorizationException
     */
    public static function asAdmin(User $actor, Closure $callback): mixed
    {
        if ($actor->isBanned() || ! $actor->can(Permission::ManagePayments->value)) {
            throw new AuthorizationException('Only admins can handle payments.');
        }

        $previous = self::$admin;
        self::$admin = $actor;

        try {
            return $callback();
        } finally {
            self::$admin = $previous;
        }
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function asSystem(Closure $callback): mixed
    {
        $previous = self::$system;
        self::$system = true;

        try {
            return $callback();
        } finally {
            self::$system = $previous;
        }
    }

    public static function allowsStatusChange(): bool
    {
        return self::$admin !== null || self::$system;
    }

    public static function admin(): ?User
    {
        return self::$admin;
    }
}
