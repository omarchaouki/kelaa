<?php

namespace App\Models;

use App\Enums\PlacementStatus;
use Database\Factories\SponsoredPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
