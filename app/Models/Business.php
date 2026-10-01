<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use App\Enums\ReviewStatus;
use App\Models\Concerns\HasGeoLocation;
use App\Models\Contracts\Translatable;
use App\Observers\FlushDirectoryCache;
use App\Services\Owners\ProfileCompleteness;
use App\Support\Phone\MoroccanPhone;
use App\Support\Security\SafeUrl;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * @property int $id
 * @property int $city_id
 * @property int|null $neighborhood_id
 * @property int|null $owner_id
 * @property int|null $submitted_by
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property float $latitude
 * @property float $longitude
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $email
 * @property string|null $website
 * @property array<string, mixed>|null $social_links
 * @property int|null $price_range
 * @property bool $is_always_open
 * @property BusinessStatus $status
 * @property list<int>|null $possible_duplicate_ids
 * @property bool $is_verified
 * @property bool $is_claimed
 * @property int|null $plan_id
 * @property Carbon|null $plan_expires_at
 * @property string|null $legal_name
 * @property string|null $ice
 * @property string|null $legal_address
 * @property int $profile_completeness
 * @property float $rating_avg
 * @property int $rating_count
 * @property float $bayesian_score
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * Organic ranking fields (rating_avg, rating_count, bayesian_score,
 * profile_completeness) are computed by jobs and never mass assignable.
 */
#[ObservedBy(FlushDirectoryCache::class)]
#[Fillable([
    'city_id', 'neighborhood_id', 'name', 'slug', 'description', 'address',
    'latitude', 'longitude', 'phone', 'whatsapp', 'email', 'website', 'social_links',
    'price_range', 'is_always_open', 'legal_name', 'ice', 'legal_address',
])]
class Business extends Model implements HasMedia, Translatable
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasGeoLocation, HasTranslations, InteractsWithMedia, Searchable, SoftDeletes;

    /** @var list<string> */
    public array $translatable = ['description'];

    /**
     * Only published listings are indexed; nothing private (owner, legal
     * info, contact details) ever reaches the search engine.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->status === BusinessStatus::Published && ! $this->trashed();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['categories', 'neighborhood']);

        $translations = fn (Translatable&Model $model, string $attribute) => implode(' ', array_filter(array_map(
            fn ($value) => is_string($value) ? $value : null,
            $model->getTranslations($attribute),
        )));

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category_names' => $this->categories->map(fn (Category $category) => $translations($category, 'name'))->implode(' '),
            'neighborhood_name' => $this->neighborhood ? $translations($this->neighborhood, 'name') : '',
            'description' => mb_substr($translations($this, 'description'), 0, 2000),
            'address' => (string) $this->address,
            'city_id' => $this->city_id,
            'category_ids' => $this->categories->pluck('id')->all(),
            'neighborhood_id' => $this->neighborhood_id,
            'is_verified' => $this->is_verified,
            'price_range' => $this->price_range,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'bayesian_score' => $this->bayesian_score,
            'published_at' => $this->published_at?->getTimestamp(),
            '_geo' => ['lat' => $this->latitude, 'lng' => $this->longitude],
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['categories', 'neighborhood']);
    }

    /** Social networks accepted in social_links. */
    public const SOCIAL_NETWORKS = ['facebook', 'instagram', 'tiktok', 'youtube', 'linkedin', 'x'];

    protected static function booted(): void
    {
        // Completeness is a ranking signal: keep it in line with the data.
        static::saved(function (Business $business): void {
            app(ProfileCompleteness::class)->refresh($business);
        });

        // Contact data ends up in href attributes: normalize it whatever wrote it.
        static::saving(function (Business $business): void {
            if ($business->isDirty('phone')) {
                $business->phone = MoroccanPhone::normalize($business->phone, mobileOnly: false);
            }

            if ($business->isDirty('whatsapp')) {
                $business->whatsapp = MoroccanPhone::normalize($business->whatsapp);
            }

            if ($business->isDirty('website')) {
                $business->website = SafeUrl::normalize($business->website);
            }

            if ($business->isDirty('email') && $business->email !== null) {
                $business->email = filter_var($business->email, FILTER_VALIDATE_EMAIL) ? mb_strtolower($business->email) : null;
            }

            if ($business->isDirty('social_links')) {
                $links = [];

                foreach ($business->social_links ?? [] as $network => $url) {
                    $safe = SafeUrl::normalize(is_string($url) ? $url : null);

                    if (in_array($network, self::SOCIAL_NETWORKS, true) && $safe !== null) {
                        $links[$network] = $safe;
                    }
                }

                $business->social_links = $links === [] ? null : $links;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'social_links' => 'array',
            'price_range' => 'integer',
            'is_always_open' => 'boolean',
            'status' => BusinessStatus::class,
            'possible_duplicate_ids' => 'array',
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
        // Small list thumbnail generated immediately; the large version and its
        // responsive variants are queued.
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->format('webp')
            ->fit(Fit::Crop, 320, 240);

        $this->addMediaConversion('large')
            ->withResponsiveImages()
            ->format('webp')
            ->fit(Fit::Max, 1280, 960);
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
     * Businesses open at the given moment. Mirrors App\Services\Hours\OpeningHours:
     * special periods replace regular hours for the dates they cover, a closing
     * time earlier than (or equal to) the opening time runs past midnight.
     * Times are stored as "HH:MM:SS" so string comparison is correct.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function openAt(Builder $query, CarbonInterface $moment, ?string $timezone = null): void
    {
        $now = CarbonImmutable::instance($moment)->setTimezone($timezone ?? config('directory.timezone'));
        $today = $now->toDateString();
        $yesterday = $now->subDay()->toDateString();
        $time = $now->format('H:i:s');

        $special = fn (string $date) => fn ($q) => $q->select(DB::raw(1))
            ->from('business_special_hours')
            ->whereColumn('business_special_hours.business_id', 'businesses.id')
            ->where('starts_on', '<=', $date)
            ->where('ends_on', '>=', $date);

        // A slot of $date that is running at $time on that same date.
        $runningToday = fn ($q) => $q->where('is_closed', false)
            ->where('opens_at', '<=', $time)
            ->where(fn ($q) => $q->where('closes_at', '>', $time)->orWhereColumn('closes_at', '<=', 'opens_at'));

        // An overnight slot of the previous date still running after midnight.
        $spillingOver = fn ($q) => $q->where('is_closed', false)
            ->whereColumn('closes_at', '<=', 'opens_at')
            ->where('closes_at', '>', $time);

        $query->where(fn (Builder $q) => $q
            ->where('businesses.is_always_open', true)
            // Special period today.
            ->orWhereExists(fn ($q) => $special($today)($q)->where($runningToday))
            ->orWhere(fn ($q) => $q
                ->whereNotExists($special($today))
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('business_hours')
                    ->whereColumn('business_hours.business_id', 'businesses.id')
                    ->where('day_of_week', $now->isoWeekday())
                    ->where($runningToday)))
            // Yesterday's overnight slot (special or regular).
            ->orWhereExists(fn ($q) => $special($yesterday)($q)->where($spillingOver))
            ->orWhere(fn ($q) => $q
                ->whereNotExists($special($yesterday))
                ->whereExists(fn ($q) => $q->select(DB::raw(1))->from('business_hours')
                    ->whereColumn('business_hours.business_id', 'businesses.id')
                    ->where('day_of_week', $now->subDay()->isoWeekday())
                    ->where($spillingOver))));
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
