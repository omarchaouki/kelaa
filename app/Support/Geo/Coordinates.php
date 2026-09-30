<?php

namespace App\Support\Geo;

use InvalidArgumentException;

final readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if ($latitude < -90 || $latitude > 90) {
            throw new InvalidArgumentException("Invalid latitude [{$latitude}].");
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new InvalidArgumentException("Invalid longitude [{$longitude}].");
        }
    }

    public static function tryFrom(mixed $latitude, mixed $longitude): ?self
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        try {
            return new self((float) $latitude, (float) $longitude);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /**
     * WKT in longitude-latitude order (use with axis-order=long-lat).
     */
    public function toWkt(): string
    {
        return sprintf('POINT(%.7F %.7F)', $this->longitude, $this->latitude);
    }

    /**
     * Great-circle distance in meters (haversine), mirrors ST_Distance_Sphere.
     */
    public function distanceTo(self $other): float
    {
        $latFrom = deg2rad($this->latitude);
        $latTo = deg2rad($other->latitude);
        $deltaLat = $latTo - $latFrom;
        $deltaLng = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;

        return GeoQuery::EARTH_RADIUS_METERS * 2 * asin(min(1.0, sqrt($a)));
    }

    /**
     * Bounding box around this point, used as a cheap index-friendly prefilter.
     *
     * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
     */
    public function boundingBox(float $meters): array
    {
        $latDelta = rad2deg($meters / GeoQuery::EARTH_RADIUS_METERS);
        $lngDelta = rad2deg($meters / (GeoQuery::EARTH_RADIUS_METERS * max(cos(deg2rad($this->latitude)), 0.000001)));

        return [
            'min_lat' => $this->latitude - $latDelta,
            'max_lat' => $this->latitude + $latDelta,
            'min_lng' => $this->longitude - $lngDelta,
            'max_lng' => $this->longitude + $lngDelta,
        ];
    }
}
