<?php

namespace App\Support\Moderation;

use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * The only way to change the status of an existing review or reply.
 *
 * - asModerator(): a moderator or admin acting in the panel.
 * - asSystem(): automatic transitions decided by the abuse rules when the
 *   AUTHOR edits their own review (e.g. back to "pending").
 *
 * Anything else that touches the status column (a business owner, a paid
 * plan, a payment callback, a stray update()) throws: see Review::booted().
 * Payment status never appears here, so money can never change visibility.
 */
final class ModerationGate
{
    private static ?User $moderator = null;

    private static bool $system = false;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     *
     * @throws AuthorizationException
     */
    public static function asModerator(User $actor, Closure $callback): mixed
    {
        if (! $actor->isStaff() || $actor->isBanned()) {
            throw new AuthorizationException('Only moderators and admins can moderate content.');
        }

        $previous = self::$moderator;
        self::$moderator = $actor;

        try {
            return $callback();
        } finally {
            self::$moderator = $previous;
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
        return self::$moderator !== null || self::$system;
    }

    public static function moderator(): ?User
    {
        return self::$moderator;
    }
}
