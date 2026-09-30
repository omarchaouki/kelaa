<?php

namespace App\Models;

use App\Models\Contracts\Translatable;
use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property string|null $description
 * @property string $slug
 * @property string $price_monthly
 * @property string $price_yearly
 * @property array<string, mixed> $features
 * @property bool $is_default
 * @property bool $is_active
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * features: max_photos (int), show_stats, highlighted_card, logo_and_cover,
 * remove_competitor_ads, priority_support (bool).
 */
#[Fillable(['name', 'slug', 'description', 'price_monthly', 'price_yearly', 'features', 'is_default', 'is_active', 'sort_order'])]
class Plan extends Model implements Translatable
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'features' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function feature(string $key, mixed $default = null): mixed
    {
        return data_get($this->features, $key, $default);
    }

    public function isFree(): bool
    {
        return (float) $this->price_monthly === 0.0 && (float) $this->price_yearly === 0.0;
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
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
