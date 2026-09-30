<?php

namespace App\Services\Ranking;

use Carbon\CarbonInterface;

/**
 * Everything the organic ranking may look at. There is deliberately no
 * field for plans, payments or sponsored placements.
 */
final readonly class RankingSignals
{
    /**
     * @param  float|null  $textRank  0 = best text match, null = no text query.
     * @param  int|null  $textHits  Number of text matches ($textRank is relative to it).
     */
    public function __construct(
        public float $bayesianScore,
        public int $profileCompleteness,
        public bool $isVerified,
        public ?CarbonInterface $lastActivityAt,
        public ?float $distanceMeters = null,
        public ?float $textRank = null,
        public ?int $textHits = null,
    ) {}
}
