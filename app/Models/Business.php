<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use App\Enums\ReviewStatus;
use App\Models\Concerns\HasGeoLocation;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * Organic ranking fields (rating_avg, rating_count, bayesian_score,
 * profile_completeness) are computed by jobs and never mass assignable.
 */
#[Fillable([
    'city_id', 'neighborhood_id', 'name', 'slug', 'description', 'address',
    'latitude', 'longitude', 'phone', 'whatsapp', 'email', 'website', 'social_links',
    'price_range', 'is_always_open', 'legal_name', 'ice', 'legal_address',
])]
class Business extends Model implements HasMedia
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasGeoLocation, HasTranslations, InteractsWithMedia, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['description'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'social_links' => 'array',
            'price_range' => 'integer',
            'is_always_open' => 'boolean',
            'status' => BusinessStatus::class,
            'is_verified' => 'boolean',
            'is_claimed' => 'boolean',
            'plan_expires_at' => 'datetime',
            'profile_completeness' => 'integer',
            'rating_avg' => 'float',
            'rating_count' => 'integer',
            'bayesian_score' => 'float',
            'last_activity_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        $this->addMediaCollection('gallery')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->format('webp')
            ->fit(Fit::Crop, 320, 240)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->format('webp')
            ->fit(Fit::Max, 1280, 960)
            ->withResponsiveImages();
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Neighborhood, $this>
     */
    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('is_primary');
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function primaryCategory(): BelongsToMany
    {
        return $this->categories()->wherePivot('is_primary', true);
    }

    /**
     * @return HasMany<BusinessHour, $this>
     */
    public function hours(): HasMany
    {
        return $this->hasMany(BusinessHour::class)->orderBy('day_of_week')->orderBy('opens_at');
    }

    /**
     * @return HasMany<BusinessSpecialHour, $this>
     */
    public function specialHours(): HasMany
    {
        return $this->hasMany(BusinessSpecialHour::class)->orderBy('starts_on');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function publishedReviews(): HasMany
    {
        return $this->reviews()->where('status', ReviewStatus::Published);
    }

    /**
     * @return HasMany<BusinessClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(BusinessClaim::class);
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<SponsoredPlacement, $this>
     */
    public function sponsoredPlacements(): HasMany
    {
        return $this->hasMany(SponsoredPlacement::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<ContactEvent, $this>
     */
    public function contactEvents(): HasMany
    {
        return $this->hasMany(ContactEvent::class);
    }

    /**
     * @return HasMany<PharmacyDuty, $this>
     */
    public function pharmacyDuties(): HasMany
    {
        return $this->hasMany(PharmacyDuty::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withPivot('created_at');
    }

    public function isPublished(): bool
    {
        return $this->status === BusinessStatus::Published;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', BusinessStatus::Published);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inCity(Builder $query, City|int $city): void
    {
        $query->where('city_id', $city instanceof City ? $city->getKey() : $city);
    }
}
