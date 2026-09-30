<?php

namespace App\Support\Security;

/**
 * Guards user-supplied URLs (business websites, social links) before they
 * are stored or rendered in an href: only absolute http(s) URLs with a real
 * host pass, so "javascript:", "data:" and protocol-relative tricks cannot.
 */
final class SafeUrl
{
    public static function isSafe(?string $url): bool
    {
        return self::normalize($url) !== null;
    }

    /**
     * Returns the URL (https:// added when the scheme is missing) or null when unsafe.
     */
    public static function normalize(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $url = trim($url);

        // Control characters and whitespace inside a URL are never legitimate here.
        if ($url === '' || preg_match('/[\x00-\x20\x7F]/', $url) === 1 || mb_strlen($url) > 2048) {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            if (str_starts_with($url, '//')) {
                return null;
            }

            $url = 'https://'.$url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        // A host needs a dot (example.ma) or must be an IP; rejects "https://javascript".
        if (! str_contains($parts['host'], '.') && filter_var($parts['host'], FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $url;
    }
}
