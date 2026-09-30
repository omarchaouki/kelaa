<?php

namespace App\Console\Commands;

use App\Jobs\SyncSearchSettings;
use App\Models\Business;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('search:sync {--import : Also re-import every published business}')]
#[Description('Push Meilisearch index settings and synonyms (and optionally re-import businesses)')]
class SyncSearchIndex extends Command
{
    public function handle(): int
    {
        SyncSearchSettings::dispatchSync();
        $this->info('Search settings and synonyms synced.');

        if ($this->option('import')) {
            $this->call('scout:import', ['model' => Business::class]);
        }

        return self::SUCCESS;
    }
}
