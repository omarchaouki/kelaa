<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use DomainException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

#[Fillable(['parent_id', 'name', 'slug', 'icon', 'sort_order', 'is_emergency', 'is_active', 'schema_type'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasTranslations;

    /** @var list<string> */
    public array $translatable = ['name'];

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            $category->guardDepth();
        });
    }

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_emergency' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Categories are limited to two levels: a group and its children.
     */
    public function guardDepth(): void
    {
        if ($this->parent_id === null) {
            return;
        }

        if ($this->exists && (int) $this->parent_id === $this->getKey()) {
            throw new DomainException('A category cannot be its own parent.');
        }

        $parent = self::query()->find($this->parent_id);

        if ($parent === null) {
            return;
        }

        if ($parent->parent_id !== null) {
            throw new DomainException('Categories are limited to two levels.');
        }

        if ($this->exists && $this->children()->exists()) {
            throw new DomainException('A category with children cannot become a child itself.');
        }
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)->withPivot('is_primary');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function roots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }
}
