<?php

namespace App\Services\Search;

use App\Enums\SearchSort;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Services\Ranking\RankingService;
use App\Services\Ranking\RankingSignals;
use App\Support\Geo\Coordinates;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

/**
 * Organic search: full-text relevance from Meilisearch (typos, synonyms,
 * Arabic) combined with SQL filters. Paid plans and sponsored placements
 * never influence the order; sponsored businesses shown above the list
 * are excluded from it so nobody appears twice.
 */
class BusinessSearch
{
    /**
     * Upper bound of text matches considered for one query.
     */
    public const MAX_TEXT_HITS = 1000;

    /**
     * Upper bound of rows scored in PHP for the relevance order.
     */
    public const MAX_RELEVANCE_CANDIDATES = 1000;

    public function __construct(
        private readonly RankingService $ranking,
    ) {}

    /**
     * Relations every result card needs (avoids N+1).
     *
     * @var list<string>
     */
    public const CARD_RELATIONS = ['categories', 'neighborhood', 'hours', 'specialHours', 'city', 'media', 'plan'];

    /**
     * @param  list<int>  $excludeIds  Businesses already shown in sponsored slots.
     * @return LengthAwarePaginator<int, Business>
     */
    public function search(City $city, SearchFilters $filters, ?Coordinates $location = null, int $perPage = 20, int $page = 1, array $excludeIds = []): LengthAwarePaginator
    {
        $query = $this->filteredQuery($city, $filters, $location)
            ->when($excludeIds !== [], fn (Builder $q) => $q->whereNotIn('businesses.id', $excludeIds));

        $sort = $filters->sort;

        if ($sort === SearchSort::Distance && $location === null) {
            $sort = SearchSort::Relevance;
        }

        $rankedIds = null;

        if ($filters->hasText()) {
            $rankedIds = $this->textMatches($city, $filters->query);
            $query->whereIn('businesses.id', $rankedIds === [] ? [0] : $rankedIds);
        }

        if ($sort === SearchSort::Relevance) {
            return $this->paginateByRelevance($query, $rankedIds, $perPage, $page, $location);
        }

        $this->applySort($query, $sort, $location);

        /** @var LengthAwarePaginator<int, Business> $results */
        $results = $query->with(self::CARD_RELATIONS)->paginate($perPage, ['*'], 'page', $page);

        return $results;
    }

    /**
     * @return Builder<Business>
     */
    public function filteredQuery(City $city, SearchFilters $filters, ?Coordinates $location = null): Builder
    {
        $query = Business::query()->published()->inCity($city)->select('businesses.*');

        if ($location !== null) {
            $query->withDistanceFrom($location);
        }

        if ($filters->categoryId !== null) {
            $categoryIds = Category::query()
                ->active()
                ->where(fn (Builder $q) => $q->whereKey($filters->categoryId)->orWhere('parent_id', $filters->categoryId))
                ->pluck('id');

            $query->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $categoryIds));
        }

        if ($filters->neighborhoodId !== null) {
            $query->where('businesses.neighborhood_id', $filters->neighborhoodId);
        }

        if ($filters->openNow) {
            $query->openAt(now(), $city->timezone);
        }

        if ($filters->minRating !== null) {
            $query->where('businesses.rating_count', '>', 0)->where('businesses.rating_avg', '>=', $filters->minRating);
        }

        if ($filters->priceRanges !== []) {
            $query->whereIn('businesses.price_range', $filters->priceRanges);
        }

        if ($filters->verifiedOnly) {
            $query->where('businesses.is_verified', true);
        }

        if ($filters->radiusKm !== null && $location !== null) {
            $query->withinDistance($location, $filters->radiusKm * 1000);
        }

        return $query;
    }

    /**
     * Business ids matching the text, best match first.
     *
     * @return list<int>
     */
    public function textMatches(City $city, string $text): array
    {
        return Business::search($text)
            ->where('city_id', $city->id)
            ->take(self::MAX_TEXT_HITS)
            ->keys()
            ->map(fn ($key) => (int) $key)
            ->values()
            ->all();
    }

    /**
     * @param  Builder<Business>  $query
     */
    private function applySort(Builder $query, SearchSort $sort, ?Coordinates $location): void
    {
        match ($sort) {
            SearchSort::Distance => $query->orderBy('distance_m'),
            SearchSort::Reviews => $query->orderByDesc('businesses.rating_count')->orderByDesc('businesses.bayesian_score'),
            SearchSort::Newest => $query->orderByDesc('businesses.published_at'),
            SearchSort::Relevance, SearchSort::Rating => $query->orderByDesc('businesses.bayesian_score')->orderByDesc('businesses.rating_count'),
        };

        $query->orderBy('businesses.id');
    }

    /**
     * Relevance order from RankingService: Bayesian score, text match rank,
     * distance, profile completeness, recent activity and verification.
     * Scores are computed over at most MAX_RELEVANCE_CANDIDATES rows.
     *
     * @param  Builder<Business>  $query
     * @param  list<int>|null  $rankedIds  Text matches, best first (null without a text query).
     * @return LengthAwarePaginator<int, Business>
     */
    private function paginateByRelevance(Builder $query, ?array $rankedIds, int $perPage, int $page, ?Coordinates $location): LengthAwarePaginator
    {
        $textPositions = $rankedIds === null ? null : array_flip($rankedIds);

        $candidates = $query->clone()
            ->reorder()
            ->orderByDesc('businesses.bayesian_score')
            ->limit(self::MAX_RELEVANCE_CANDIDATES)
            ->get(['businesses.id', 'businesses.bayesian_score', 'businesses.profile_completeness', 'businesses.is_verified', 'businesses.last_activity_at', 'businesses.latitude', 'businesses.longitude']);

        $scores = [];

        foreach ($candidates as $candidate) {
            $scores[$candidate->id] = $this->ranking->relevance(new RankingSignals(
                bayesianScore: $candidate->bayesian_score,
                profileCompleteness: $candidate->profile_completeness,
                isVerified: $candidate->is_verified,
                lastActivityAt: $candidate->last_activity_at,
                distanceMeters: $location !== null && ($coordinates = $candidate->coordinates()) !== null ? $location->distanceTo($coordinates) : null,
                textRank: $textPositions === null ? null : (float) ($textPositions[$candidate->id] ?? count($textPositions)),
                textHits: $textPositions === null ? null : count($textPositions),
            ));
        }

        // Stable ordering: score, then id, so pages never overlap.
        uksort($scores, fn (int $a, int $b) => [$scores[$b], $a] <=> [$scores[$a], $b]);
        $ordered = array_keys($scores);

        $pageIds = array_slice($ordered, ($page - 1) * $perPage, $perPage);

        $models = Business::query()
            ->select('businesses.*')
            ->when($location !== null, fn (Builder $q) => $q->withDistanceFrom($location))
            ->whereIn('businesses.id', $pageIds === [] ? [0] : $pageIds)
            ->with(self::CARD_RELATIONS)
            ->get()
            ->sortBy(fn (Business $business) => array_search($business->id, $pageIds, true))
            ->values();

        return new Paginator(new Collection($models->all()), count($ordered), $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => 'page',
        ]);
    }
}
