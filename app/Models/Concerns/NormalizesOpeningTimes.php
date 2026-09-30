<?php

namespace App\Models\Concerns;

use App\Support\Hours\TimeOfDay;
use Illuminate\Database\Eloquent\Model;

/**
 * Stores opens_at / closes_at as "HH:MM:SS" so times compare correctly as
 * strings in SQL (the "open now" scope) on every database engine.
 *
 * @mixin Model
 */
trait NormalizesOpeningTimes
{
    public static function bootNormalizesOpeningTimes(): void
    {
        static::saving(function (Model $model): void {
            foreach (['opens_at', 'closes_at'] as $attribute) {
                $value = $model->getAttribute($attribute);
                $model->setAttribute($attribute, TimeOfDay::normalize(is_string($value) ? $value : null));
            }

            // A slot needs both ends; anything else is a closed day.
            if ($model->getAttribute('opens_at') === null || $model->getAttribute('closes_at') === null) {
                $model->setAttribute('opens_at', null);
                $model->setAttribute('closes_at', null);
                $model->setAttribute('is_closed', true);
            }
        });
    }
}
