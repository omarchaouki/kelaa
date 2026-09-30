<?php

namespace App\Models\Concerns;

use App\Support\Geo\Coordinates;
use App\Support\Geo\GeoQuery;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * For models with latitude/longitude columns plus a spatial POINT column
 * (see SpatialSchema). Latitude/longitude are the source of truth; the
 * POINT is derived from them on save.
 *
 * @mixin Model
 */
trait HasGeoLocation
{
    public static function bootHasGeoLocation(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            $column = $model->geoPointColumn();

            if (! $model->isDirty(['latitude', 'longitude']) && $model->exists) {
                return;
            }

            $coordinates = $model->coordinates();

            $model->setAttribute($column, $coordinates === null
                ? null
                : GeoQuery::pointExpression($model->getConnection(), $coordinates));
        });

        // The raw expression must not linger on the in-memory model
        // (Eloquent re-syncs the original attributes right after "saved").
        static::saved(function (Model $model): void {
            /** @var Model&self $model */
            $model->offsetUnset($model->geoPointColumn());
        });
    }

    public function initializeHasGeoLocation(): void
    {
        $this->makeHidden($this->geoPointColumn());
    }

    public function geoPointColumn(): string
    {
        return 'location';
    }

    public function coordinates(): ?Coordinates
    {
        return Coordinates::tryFrom($this->getAttribute('latitude'), $this->getAttribute('longitude'));
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withDistanceFrom(Builder $query, Coordinates $from): void
    {
        GeoQuery::selectDistance($query, $from, $this->geoPointColumn());
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function withinDistance(Builder $query, Coordinates $from, float $meters): void
    {
        GeoQuery::whereWithin($query, $from, $meters, $this->geoPointColumn());
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function orderByDistance(Builder $query, Coordinates $from, string $direction = 'asc'): void
    {
        GeoQuery::orderByDistance($query, $from, $direction, $this->geoPointColumn());
    }
}
