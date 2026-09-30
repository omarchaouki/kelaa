<?php

namespace App\Models;

use Database\Factories\BusinessSpecialHourFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Replaces regular hours between starts_on and ends_on (Ramadan, Eid, holidays).
 * Several rows over the same period describe split hours.
 */
#[Fillable(['business_id', 'label', 'starts_on', 'ends_on', 'opens_at', 'closes_at', 'is_closed'])]
class BusinessSpecialHour extends Model
{
    /** @use HasFactory<BusinessSpecialHourFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['label'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
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
