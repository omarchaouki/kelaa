<?php

namespace App\Casts;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Calendar date stored as "Y-m-d" (Laravel's "date" cast stores a full
 * datetime string, which breaks date comparisons on SQLite).
 *
 * @implements CastsAttributes<Carbon|null, CarbonInterface|string|null>
 */
class DateOnly implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null ? null : Carbon::parse((string) $value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof CarbonInterface ? $value : Carbon::parse((string) $value))->toDateString();
    }
}
