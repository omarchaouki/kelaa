<?php

namespace App\Observers;

use App\Jobs\SyncSearchSettings;

class SearchSynonymObserver
{
    public function saved(): void
    {
        SyncSearchSettings::dispatch()->afterCommit();
    }

    public function deleted(): void
    {
        SyncSearchSettings::dispatch()->afterCommit();
    }
}
