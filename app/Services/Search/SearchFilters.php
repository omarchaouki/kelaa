<?php

namespace App\Services\Search;

use App\Enums\SearchSort;

/**
 * Validated search/filter state. Built from untrusted input (URL query
 * string, Livewire properties): anything outside the whitelists below is
 * dropped instead of reaching a query.
 */
final readonly class SearchFilters
{
    public const MAX_QUERY_LENGTH = 100;

    public const MIN_RATINGS = [3.0, 3.5, 4.0, 4.5];

    public const RADII_KM = [1, 2, 5, 10, 25];

    /**
     * @param  list<int>  $priceRanges
     */
    public function __construct(
        public string $query = '',
        public ?int $categoryId = null,
        public ?int $neighborhoodId = null,
        public bool $openNow = false,
        public ?float $minRating = null,
        public array $priceRanges = [],
        public bool $verifiedOnly = false,
        public ?int $radiusKm = null,
        public SearchSort $sort = SearchSort::Relevance,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public static function fromInput(array $input, ?int $categoryId = null, ?int $neighborhoodId = null): self
    {
        $query = is_string($input['q'] ?? null) ? self::cleanQuery($input['q']) : '';

        $minRating = is_numeric($input['rating'] ?? null) ? (float) $input['rating'] : null;
        $radius = is_numeric($input['radius'] ?? null) ? (int) $input['radius'] : null;

        $prices = $input['price'] ?? [];
        $prices = is_array($prices) ? $prices : explode(',', (string) $prices);
        $prices = array_values(array_unique(array_filter(
            array_map(fn ($value) => is_numeric($value) ? (int) $value : 0, $prices),
            fn (int $value) => $value >= 1 && $value <= 4,
        )));
        sort($prices);

        return new self(
            query: $query,
            categoryId: $categoryId,
            neighborhoodId: $neighborhoodId,
            openNow: filter_var($input['open'] ?? false, FILTER_VALIDATE_BOOLEAN),
            minRating: in_array($minRating, self::MIN_RATINGS, true) ? $minRating : null,
            priceRanges: $prices,
            verifiedOnly: filter_var($input['verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            radiusKm: in_array($radius, self::RADII_KM, true) ? $radius : null,
            sort: SearchSort::tryFrom(is_string($input['sort'] ?? null) ? $input['sort'] : '') ?? SearchSort::Relevance,
        );
    }

    /**
     * Collapses whitespace, strips control characters, caps the length.
     */
    public static function cleanQuery(string $query): string
    {
        $query = preg_replace('/[\p{C}]+/u', ' ', $query) ?? '';
        $query = preg_replace('/\s+/u', ' ', $query) ?? '';

        return mb_substr(trim($query), 0, self::MAX_QUERY_LENGTH);
    }

    public function hasText(): bool
    {
        return $this->query !== '';
    }
}
