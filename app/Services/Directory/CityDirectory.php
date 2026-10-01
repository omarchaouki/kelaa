<?php

namespace App\Services\Directory;

use App\Enums\ReviewStatus;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\PharmacyDuty;
use App\Services\Search\BusinessSearch;
use App\Support\DirectoryCache;
use App\Support\Geo\Coordinates;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read models for the city home page and listings. Shared results are cached
 * as ids/arrays only: the cache never holds objects (cache.serializable_classes = false).
 */
class CityDirectory
{
    /**
     * Active category groups with their active children, and the number of
     * published businesses of this city in each category.
     *
     * @return array{groups: Collection<int, Category>, counts: array<int, int>}
     */
    public function categoryTree(City $city): array
    {
        // Small, indexed query: not cached (the cache only holds scalars and arrays).
        $groups = Category::query()->active()->roots()->ordered()
            ->with(['children' => fn ($q) => $q->where('is_active', true)])
            ->get();

        $counts = DirectoryCache::remember("city:{$city->id}:category-counts", now()->addHour(), function () use ($city): array {
            return DB::table('business_category')
                ->join('businesses', 'businesses.id', '=', 'business_category.business_id')
                ->where('businesses.city_id', $city->id)
                ->where('businesses.status', 'published')
                ->whereNull('businesses.deleted_at')
                ->groupBy('business_category.category_id')
                ->selectRaw('business_category.category_id as category_id, count(distinct businesses.id) as total')
                ->pluck('total', 'category_id')
                ->map(fn ($count) => (int) $count)
                ->all();
        });

        return ['groups' => $groups, 'counts' => $counts];
    }

    /**
     * Pharmacies on duty now, then those starting later tonight.
     *
     * @return Collection<int, PharmacyDuty>
     */
    public function pharmaciesOnDuty(City $city, ?CarbonImmutable $at = null, int $hoursAhead = 18): Collection
    {
        $at ??= now()->toImmutable();
        $bucket = $at->format('Y-m-d-H');

        $ids = DirectoryCache::remember("city:{$city->id}:pharmacies:{$bucket}:{$hoursAhead}", now()->addMinutes(10), fn () => PharmacyDuty::query()
            ->where('ends_at', '>', $at)
            ->where('starts_at', '<', $at->addHours($hoursAhead))
            ->whereHas('business', fn (Builder $q) => $q->published()->inCity($city))
            ->orderBy('starts_at')
            ->pluck('id')
            ->all());

        return PharmacyDuty::query()
            ->whereKey($ids)
            ->with(['business' => fn ($q) => $q->with(['neighborhood', 'city'])])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return Collection<int, Business>
     */
    public function recentlyReviewed(City $city, int $limit = 6): Collection
    {
        $ids = DirectoryCache::remember("city:{$city->id}:recently-reviewed:{$limit}", now()->addMinutes(10), function () use ($city, $limit): array {
            $latest = DB::table('reviews')
                ->select('business_id', DB::raw('max(published_at) as last_review_at'))
                ->where('status', ReviewStatus::Published->value)
                ->groupBy('business_id');

            return Business::query()->published()->inCity($city)
                ->select('businesses.*')
                ->joinSub($latest, 'latest_reviews', 'latest_reviews.business_id', '=', 'businesses.id')
                ->orderByDesc('latest_reviews.last_review_at')
                ->limit($limit)
                ->pluck('businesses.id')
                ->all();
        });

        return Business::query()->published()->whereKey($ids)
            ->with(BusinessSearch::CARD_RELATIONS)
            ->get()
            ->sortBy(fn (Business $business) => array_search($business->id, $ids, false))
            ->values();
    }

    /**
     * Open businesses around the visitor (not cached: depends on the visitor).
     *
     * @return Collection<int, Business>
     */
    public function openNearby(City $city, Coordinates $location, int $limit = 6, int $radiusMeters = 3000): Collection
    {
        return Business::query()->published()->inCity($city)
            ->select('businesses.*')
            ->withDistanceFrom($location)
            ->withinDistance($location, $radiusMeters)
            ->openAt(now(), $city->timezone)
            ->orderBy('distance_m')
            ->limit($limit)
            ->with(BusinessSearch::CARD_RELATIONS)
            ->get();
    }

    /**
     * Organic neighbours with the same primary category (no sponsored content).
     *
     * @return Collection<int, Business>
     */
    public function similarNearby(Business $business, int $limit = 4, int $radiusMeters = 5000): Collection
    {
        $category = $business->categories->firstWhere('pivot.is_primary', true) ?? $business->categories->first();
        $coordinates = $business->coordinates();

        if ($category === null || $coordinates === null) {
            return new Collection;
        }

        return Business::query()->published()->inCity($business->city_id)
            ->select('businesses.*')
            ->whereKeyNot($business->id)
            ->whereHas('categories', fn (Builder $q) => $q->whereKey($category->id))
            ->withDistanceFrom($coordinates)
            ->withinDistance($coordinates, $radiusMeters)
            ->orderByDesc('bayesian_score')
            ->limit($limit)
            ->with(BusinessSearch::CARD_RELATIONS)
            ->get();
    }
}
