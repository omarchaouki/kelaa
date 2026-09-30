<?php

namespace App\Support\Geo;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;

/**
 * The only place that knows how distance is computed in SQL.
 *
 * MySQL uses ST_Distance_Sphere on the POINT column. Every other driver
 * falls back to a haversine formula on the latitude/longitude columns
 * (SQLite gets the math functions registered in AppServiceProvider).
 * A PostGIS implementation would only need a new branch here.
 */
class GeoQuery
{
    public const EARTH_RADIUS_METERS = 6371008.8;

    /**
     * @return array{0: string, 1: list<float|string>}
     */
    public static function distanceSql(Connection $connection, string $table, Coordinates $from, string $pointColumn = 'location'): array
    {
        $grammar = $connection->getQueryGrammar();

        if (self::usesMySqlSpatial($connection)) {
            return [
                sprintf(
                    "ST_Distance_Sphere(%s, ST_GeomFromText(?, 4326, 'axis-order=long-lat'))",
                    $grammar->wrap("{$table}.{$pointColumn}"),
                ),
                [$from->toWkt()],
            ];
        }

        $lat = $grammar->wrap("{$table}.latitude");
        $lng = $grammar->wrap("{$table}.longitude");

        return [
            sprintf(
                '(%F * 2 * asin(sqrt(power(sin(radians(%s - ?) / 2), 2) + cos(radians(?)) * cos(radians(%s)) * power(sin(radians(%s - ?) / 2), 2))))',
                self::EARTH_RADIUS_METERS,
                $lat,
                $lat,
                $lng,
            ),
            [$from->latitude, $from->latitude, $from->longitude],
        ];
    }

    /**
     * Adds a "distance_m" column (meters).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function selectDistance(Builder $query, Coordinates $from, string $pointColumn = 'location'): Builder
    {
        $table = $query->getModel()->getTable();
        [$sql, $bindings] = self::distanceSql($query->getModel()->getConnection(), $table, $from, $pointColumn);

        if ($query->getQuery()->columns === null) {
            $query->select("{$table}.*");
        }

        return $query->selectRaw("{$sql} as distance_m", $bindings);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function whereWithin(Builder $query, Coordinates $from, float $meters, string $pointColumn = 'location'): Builder
    {
        $table = $query->getModel()->getTable();
        $box = $from->boundingBox($meters);
        [$sql, $bindings] = self::distanceSql($query->getModel()->getConnection(), $table, $from, $pointColumn);

        return $query
            ->whereBetween("{$table}.latitude", [$box['min_lat'], $box['max_lat']])
            ->whereBetween("{$table}.longitude", [$box['min_lng'], $box['max_lng']])
            ->whereRaw("{$sql} <= ?", [...$bindings, $meters]);
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function orderByDistance(Builder $query, Coordinates $from, string $direction = 'asc', string $pointColumn = 'location'): Builder
    {
        $table = $query->getModel()->getTable();
        [$sql, $bindings] = self::distanceSql($query->getModel()->getConnection(), $table, $from, $pointColumn);

        return $query->orderByRaw("{$sql} {$direction}", $bindings);
    }

    /**
     * SQL expression that builds the POINT value for an insert/update.
     */
    public static function pointExpression(Connection $connection, Coordinates $coordinates): ?Expression
    {
        if (! self::usesMySqlSpatial($connection)) {
            return null;
        }

        return $connection->raw(sprintf(
            "ST_GeomFromText('%s', 4326, 'axis-order=long-lat')",
            $coordinates->toWkt(),
        ));
    }

    public static function usesMySqlSpatial(Connection $connection): bool
    {
        return in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);
    }
}
