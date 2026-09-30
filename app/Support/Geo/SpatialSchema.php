<?php

namespace App\Support\Geo;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a WGS84 point column next to plain latitude/longitude columns.
 *
 * On MySQL the point is a NOT NULL `POINT SRID 4326` with a spatial index,
 * kept in sync by the HasGeoLocation trait. Other drivers (SQLite in fast
 * local test runs) get a nullable column and rely on latitude/longitude.
 */
class SpatialSchema
{
    public static function point(Blueprint $table, string $column = 'location', bool $required = true): void
    {
        $table->decimal('latitude', 10, 7)->nullable($required === false);
        $table->decimal('longitude', 10, 7)->nullable($required === false);

        if (self::supportsSpatialIndex() && $required) {
            $table->geometry($column, subtype: 'point', srid: 4326);
            $table->spatialIndex($column);

            return;
        }

        $table->geometry($column, subtype: 'point', srid: 4326)->nullable();
    }

    public static function supportsSpatialIndex(): bool
    {
        return in_array(Schema::getConnection()->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
