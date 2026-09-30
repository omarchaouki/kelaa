<?php

namespace App\Support;

/**
 * Path segments used by the application itself. Cities and categories
 * live at /{locale}/{city}/{category}, so their slugs must never collide.
 */
final class ReservedSlugs
{
    public const ALL = [
        // /{locale}/...
        'login', 'logout', 'account', 'p', 'events', 'auth',
        // /{locale}/{city}/...
        'b', 'recherche', 'search', 'carte', 'pharmacies-de-garde',
        // Top-level application paths.
        'admin', 'livewire', 'horizon', 'api', 'up', 'build', 'storage', 'vendor', 'dashboard',
        'sitemap', 'robots', 'manifest', 'offline',
    ];

    public static function isReserved(?string $slug): bool
    {
        return $slug !== null && in_array(mb_strtolower($slug), self::ALL, true);
    }
}
