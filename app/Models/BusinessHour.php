<?php

namespace App\Models;

use Database\Factories\BusinessHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $business_id
 * @property int $day_of_week
 * @property string|null $opens_at
 * @property string|null $closes_at
 * @property bool $is_closed
 *
 * One opening slot. A day may have several slots (split hours).
 * day_of_week follows ISO-8601: 1 = Monday ... 7 = Sunday.
 */
#[Fillable(['business_id', 'day_of_week', 'opens_at', 'closes_at', 'is_closed'])]
#[WithoutTimestamps]
class BusinessHour extends Model
{
    /** @use HasFactory<BusinessHourFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'is_closed' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
