<?php

namespace App\Models;

use App\Models\Concerns\HasGeoLocation;
use App\Models\Contracts\Translatable;
use App\Observers\FlushDirectoryCache;
use Database\Factories\NeighborhoodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $city_id
 * @property string $name
 * @property string $slug
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array<mixed>|null $boundary
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(FlushDirectoryCache::class)]
#[Fillable(['city_id', 'name', 'slug', 'latitude', 'longitude', 'boundary', 'is_active'])]
class Neighborhood extends Model implements Translatable
{
    /** @use HasFactory<NeighborhoodFactory> */
    use HasFactory, HasGeoLocation, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'boundary' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function geoPointColumn(): string
    {
        return 'center';
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
