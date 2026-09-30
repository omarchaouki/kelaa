<?php

namespace App\Support;

use App\Support\Geo\Coordinates;

/**
 * The visitor's approximate position ("use my location"). Kept only in the
 * server-side session, rounded to ~110 m, and never written to URLs,
 * logs or the database, so shared links do not leak where someone is.
 */
final class VisitorLocation
{
    private const SESSION_KEY = 'visitor.location';

    public static function get(): ?Coordinates
    {
        $value = session(self::SESSION_KEY);

        return is_array($value) ? Coordinates::tryFrom($value['lat'] ?? null, $value['lng'] ?? null) : null;
    }

    public static function set(mixed $latitude, mixed $longitude): ?Coordinates
    {
        $coordinates = Coordinates::tryFrom($latitude, $longitude);

        if ($coordinates === null) {
            return null;
        }

        $rounded = new Coordinates(round($coordinates->latitude, 3), round($coordinates->longitude, 3));
        session([self::SESSION_KEY => ['lat' => $rounded->latitude, 'lng' => $rounded->longitude]]);

        return $rounded;
    }

    public static function forget(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
