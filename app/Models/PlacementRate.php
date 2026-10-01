<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $city_id
 * @property int|null $category_id
 * @property int $position
 * @property string $price_week
 * @property string $price_month
 * @property int $neighborhood_percent
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['city_id', 'category_id', 'position', 'price_week', 'price_month', 'neighborhood_percent', 'is_active'])]
class PlacementRate extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'price_week' => 'decimal:2',
            'price_month' => 'decimal:2',
            'neighborhood_percent' => 'integer',
            'is_active' => 'boolean',
        ];
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
}
