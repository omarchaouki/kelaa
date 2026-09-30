<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Search\SearchIndexSettings;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Meilisearch\Client;

/**
 * Pushes index settings and synonyms to Meilisearch. Dispatched when the
 * synonyms change in the admin; unique so bursts of edits sync once.
 */
class SyncSearchSettings implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 60;

    public function handle(SearchIndexSettings $settings): void
    {
        if (config('scout.driver') !== 'meilisearch') {
            return;
        }

        $index = (new Business)->searchableAs();

        app(Client::class)->index($index)->updateSettings($settings->forBusinesses());
    }
}
