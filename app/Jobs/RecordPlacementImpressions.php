<?php

namespace App\Jobs;

use App\Models\SponsoredPlacement;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordPlacementImpressions implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<int>  $placementIds
     */
    public function __construct(
        public array $placementIds,
    ) {}

    public function handle(): void
    {
        if ($this->placementIds !== []) {
            SponsoredPlacement::query()->whereKey($this->placementIds)->increment('impressions');
        }
    }
}
