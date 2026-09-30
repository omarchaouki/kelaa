<?php

namespace App\Services\Search;

use App\Models\SearchSynonym;

/**
 * Meilisearch settings for the "businesses" index, including the synonym
 * groups managed in the admin panel (French / Arabic / Darija in Latin letters).
 */
class SearchIndexSettings
{
    /**
     * @return array<string, mixed>
     */
    public function forBusinesses(): array
    {
        return [
            'searchableAttributes' => [
                'name',
                'category_names',
                'neighborhood_name',
                'description',
                'address',
            ],
            'filterableAttributes' => [
                'city_id',
                'category_ids',
                'neighborhood_id',
                'is_verified',
                'price_range',
                'rating_avg',
                '_geo',
            ],
            'sortableAttributes' => [
                'bayesian_score',
                'rating_count',
                'published_at',
                '_geo',
            ],
            // Tuned for short local queries typed on phones ("plombi", "mikanik").
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8],
            ],
            'synonyms' => $this->synonyms(),
            'pagination' => ['maxTotalHits' => 1000],
        ];
    }

    /**
     * Every word of a group points to all the others (bidirectional).
     *
     * @return array<string, list<string>>
     */
    public function synonyms(): array
    {
        $map = [];

        SearchSynonym::query()->orderBy('id')->each(function (SearchSynonym $group) use (&$map): void {
            $words = collect([$group->term, ...$group->synonyms])
                ->map(fn ($word) => mb_strtolower(trim((string) $word)))
                ->filter(fn (string $word) => $word !== '' && mb_strlen($word) <= 100)
                ->unique()
                ->values();

            foreach ($words as $word) {
                $map[$word] = collect([...($map[$word] ?? []), ...$words->reject(fn ($other) => $other === $word)])
                    ->unique()->values()->all();
            }
        });

        ksort($map);

        return $map;
    }
}
