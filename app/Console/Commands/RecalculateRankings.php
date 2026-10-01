<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Owners\ProfileCompleteness;
use App\Services\Ranking\RankingService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ranking:recalculate')]
#[Description('Recompute rating averages and Bayesian scores of every business')]
class RecalculateRankings extends Command
{
    public function handle(RankingService $ranking, ProfileCompleteness $completeness): int
    {
        $averages = [];
        $count = 0;

        Business::query()->withTrashed()->chunkById(500, function ($businesses) use ($ranking, $completeness, &$averages, &$count): void {
            foreach ($businesses as $business) {
                $categoryId = $ranking->primaryCategoryId($business);
                $key = $business->city_id.':'.$categoryId;
                $averages[$key] ??= $ranking->categoryAverage($business->city_id, $categoryId);

                $ranking->recalculate($business, $averages[$key]);
                $completeness->refresh($business);
                $count++;
            }
        });

        $this->info("{$count} businesses recalculated.");

        return self::SUCCESS;
    }
}
