<?php

namespace App\Support\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Keyed hashes used to spot several reviews coming from the same network or
 * device. Raw IPs and device ids are never stored, and the hashes cannot be
 * reversed without the application key.
 */
final class Fingerprint
{
    public const DEVICE_COOKIE = 'did';

    public static function ip(Request $request): string
    {
        return self::hash('ip|'.$request->ip());
    }

    /**
     * Hash of a long-lived random device cookie (created when missing).
     */
    public static function device(Request $request): string
    {
        $id = $request->cookie(self::DEVICE_COOKIE);

        if (! is_string($id) || ! Str::isUuid($id)) {
            $id = (string) Str::uuid();
            Cookie::queue(Cookie::make(self::DEVICE_COOKIE, $id, 60 * 24 * 365 * 2, httpOnly: true, sameSite: 'lax'));
        }

        return self::hash('device|'.$id);
    }

    public static function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
