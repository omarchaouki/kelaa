<?php

namespace App\Models;

use App\Enums\PlacementStatus;
use Database\Factories\SponsoredPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $business_id
 * @property int $city_id
 * @property int $category_id
 * @property int|null $neighborhood_id
 * @property int $position
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property PlacementStatus $status
 * @property int|null $payment_id
 * @property int $impressions
 * @property int $clicks
 * @property Carbon|null $reminded_7d_at
 * @property Carbon|null $reminded_1d_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['business_id', 'city_id', 'category_id', 'neighborhood_id', 'position', 'starts_at', 'ends_at', 'payment_id'])]
class SponsoredPlacement extends Model
{
    /** @use HasFactory<SponsoredPlacementFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => PlacementStatus::class,
            'impressions' => 'integer',
            'clicks' => 'integer',
            'reminded_7d_at' => 'datetime',
            'reminded_1d_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Neighborhood, $this>
     */
    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
