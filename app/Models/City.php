<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use App\Observers\FlushDirectoryCache;
use App\Support\Locales;
use App\Support\ReservedSlugs;
use Database\Factories\CityFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $center_latitude
 * @property float $center_longitude
 * @property int $default_zoom
 * @property string $timezone
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(FlushDirectoryCache::class)]
#[Fillable(['name', 'slug', 'center_latitude', 'center_longitude', 'default_zoom', 'timezone', 'is_active'])]
class City extends Model implements Translatable
{
    /** @use HasFactory<CityFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected static function booted(): void
    {
        static::saving(function (City $city): void {
            if (ReservedSlugs::isReserved($city->slug) || Locales::isSupported($city->slug)) {
                throw new DomainException("The slug [{$city->slug}] is reserved.");
            }
        });
    }

    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'default_zoom' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return HasMany<Neighborhood, $this>
     */
    public function neighborhoods(): HasMany
    {
        return $this->hasMany(Neighborhood::class);
    }

    /**
     * @return HasMany<Business, $this>
     */
    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
