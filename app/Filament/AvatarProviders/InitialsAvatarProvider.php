<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Initials on a coloured disc, built locally as an SVG data URI. Replaces
 * the default provider, which sends every staff member's name to a third-party
 * service (and is blocked by our Content-Security-Policy anyway).
 */
class InitialsAvatarProvider implements AvatarProvider
{
    /** Same palette as the public site's avatars. */
    private const COLORS = ['#1877f2', '#42b72a', '#f5533d', '#a033ff', '#f7b928', '#0a7c86', '#e42645', '#4b4c4f'];

    public function get(Model|Authenticatable $record): string
    {
        $name = trim((string) Filament::getNameForDefaultAvatar($record));
        $initial = $name === '' ? '?' : mb_strtoupper(mb_substr($name, 0, 1));
        $color = self::COLORS[abs(crc32($name)) % count(self::COLORS)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">'
            .'<rect width="64" height="64" fill="'.$color.'"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#fff" '
            .'font-family="Segoe UI, system-ui, sans-serif" font-size="28" font-weight="700">'
            .htmlspecialchars($initial, ENT_XML1 | ENT_QUOTES, 'UTF-8')
            .'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
