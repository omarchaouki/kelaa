<?php

namespace App\Services\Ranking;

use App\Enums\ReviewStatus;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

/**
 * Organic ranking (spec §7).
 *
 * Bayesian average: score = (v / (v + m)) * R + (m / (v + m)) * C
 *   R = the business's average rating, v = its number of reviews,
 *   C = average rating in its primary category and city, m = config('ranking.bayesian_m').
 *
 * Relevance combines the Bayesian score with text relevance, distance,
 * profile completeness, recency of activity and verification, using the
 * weights in config/ranking.php. Paid plans cannot influence it: they are
 * not part of RankingSignals.
 */
class RankingService
{
    public function bayesian(float $average, int $count, float $categoryAverage, ?float $m = null): float
    {
        $m ??= (float) config('ranking.bayesian_m');

        if ($count <= 0) {
            return round($categoryAverage, 4);
        }

        return round(($count / ($count + $m)) * $average + ($m / ($count + $m)) * $categoryAverage, 4);
    }

    /**
     * C: average of published review ratings for businesses sharing the primary category in the city.
     */
    public function categoryAverage(int $cityId, ?int $categoryId): float
    {
        if ($categoryId === null) {
            return (float) config('ranking.fallback_average');
        }

        $average = DB::table('reviews')
            ->join('businesses', 'businesses.id', '=', 'reviews.business_id')
            ->join('business_category', fn ($join) => $join
                ->on('business_category.business_id', '=', 'businesses.id')
                ->where('business_category.is_primary', true))
            ->where('business_category.category_id', $categoryId)
            ->where('businesses.city_id', $cityId)
            ->whereNull('businesses.deleted_at')
            ->where('reviews.status', ReviewStatus::Published->value)
            ->avg('reviews.rating');

        return $average === null ? (float) config('ranking.fallback_average') : (float) $average;
    }

    /**
     * Recomputes rating_avg, rating_count and bayesian_score from published reviews.
     */
    public function recalculate(Business $business, ?float $categoryAverage = null): void
    {
        $stats = DB::table('reviews')
            ->where('business_id', $business->id)
            ->where('status', ReviewStatus::Published->value)
            ->selectRaw('count(*) as total, avg(rating) as average')
            ->first();

        $count = (int) ($stats->total ?? 0);
        $average = $count > 0 ? (float) $stats->average : 0.0;
        $categoryAverage ??= $this->categoryAverage($business->city_id, $this->primaryCategoryId($business));

        // Query builder update: ranking columns are not mass assignable and
        // must not trigger model events (search re-indexing is done by the job).
        Business::query()->whereKey($business->id)->toBase()->update([
            'rating_avg' => round($average, 2),
            'rating_count' => $count,
            'bayesian_score' => $this->bayesian($average, $count, $categoryAverage),
        ]);
    }

    public function primaryCategoryId(Business $business): ?int
    {
        $id = DB::table('business_category')
            ->where('business_id', $business->id)
            ->where('is_primary', true)
            ->value('category_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Relevance between 0 and 1 (higher is better).
     */
    public function relevance(RankingSignals $signals): float
    {
        $weights = config('ranking.weights');

        $scores = [
            'bayesian' => min(1.0, max(0.0, $signals->bayesianScore / 5)),
            'completeness' => min(1.0, max(0.0, $signals->profileCompleteness / 100)),
            'verified' => $signals->isVerified ? 1.0 : 0.0,
            'recency' => $this->recency($signals),
        ];

        if ($signals->textRank !== null && $signals->textHits !== null && $signals->textHits > 0) {
            $scores['text'] = 1 - ($signals->textRank / max(1, $signals->textHits));
        }

        if ($signals->distanceMeters !== null) {
            $horizon = (float) config('ranking.distance_horizon_km') * 1000;
            $scores['distance'] = max(0.0, 1 - $signals->distanceMeters / max(1.0, $horizon));
        }

        $total = 0.0;
        $weightSum = 0.0;

        foreach ($scores as $signal => $score) {
            $weight = (float) ($weights[$signal] ?? 0);
            $total += $weight * $score;
            $weightSum += $weight;
        }

        return $weightSum > 0 ? round($total / $weightSum, 6) : 0.0;
    }

    private function recency(RankingSignals $signals): float
    {
        if ($signals->lastActivityAt === null) {
            return 0.0;
        }

        $horizon = max(1, (int) config('ranking.recency_horizon_days'));
        $days = max(0, $signals->lastActivityAt->diffInDays(now()));

        return max(0.0, 1 - $days / $horizon);
    }
}
