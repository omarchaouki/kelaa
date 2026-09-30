<?php

namespace App\Services\Tracking;

use Illuminate\Http\Request;

/**
 * Anonymous visitor identity for statistics. The hash changes every day and
 * cannot be linked back to a session, an IP address or an account.
 */
final class Visitor
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview|headless|lighthouse|curl|wget|python-requests|httpclient/i';

    public static function dailyHash(Request $request): string
    {
        $session = $request->hasSession() ? $request->session()->getId() : (string) $request->ip();

        return hash_hmac('sha256', $session.'|'.now()->toDateString(), (string) config('app.key'));
    }

    /**
     * Keyed hash of IP + user agent, only used as a short-lived cache key (never stored).
     */
    public static function networkHash(Request $request): string
    {
        return hash_hmac('sha256', $request->ip().'|'.$request->userAgent(), (string) config('app.key'));
    }

    public static function isBot(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match(self::BOT_PATTERN, $agent) === 1;
    }
}
