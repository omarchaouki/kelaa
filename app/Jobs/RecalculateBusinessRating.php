<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Ranking\RankingService;
use App\Support\DirectoryCache;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

/**
 * Runs after a review is published, edited, hidden or removed.
 *
 * The category average (C) moves too, so every business sharing the
 * primary category in the city gets a fresh Bayesian score.
 */
class RecalculateBusinessRating implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function __construct(
        public int $businessId,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->businessId;
    }

    public function handle(RankingService $ranking): void
    {
        $business = Business::withTrashed()->find($this->businessId);

        if ($business === null) {
            return;
        }

        $categoryId = $ranking->primaryCategoryId($business);
        $categoryAverage = $ranking->categoryAverage($business->city_id, $categoryId);

        $peerIds = $categoryId === null
            ? [$business->id]
            : DB::table('business_category')
                ->join('businesses', 'businesses.id', '=', 'business_category.business_id')
                ->where('business_category.category_id', $categoryId)
                ->where('business_category.is_primary', true)
                ->where('businesses.city_id', $business->city_id)
                ->pluck('businesses.id')
                ->all();

        Business::withTrashed()->whereKey($peerIds)->each(function (Business $peer) use ($ranking, $categoryAverage): void {
            $ranking->recalculate($peer, $categoryAverage);
        });

        // Keep the search index and cached pages in line with the new numbers.
        $business->refresh()->searchable();
        DirectoryCache::flush();
    }
}
