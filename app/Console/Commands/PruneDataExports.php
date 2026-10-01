<?php

namespace App\Console\Commands;

use App\Jobs\ExportUserData;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('privacy:prune-exports')]
#[Description('Delete personal data exports older than their download window')]
class PruneDataExports extends Command
{
    public function handle(): int
    {
        $disk = Storage::disk('local');
        $limit = now()->subDays(ExportUserData::LIFETIME_DAYS)->getTimestamp();
        $deleted = 0;

        foreach ($disk->allFiles(ExportUserData::DIRECTORY) as $file) {
            if ($disk->lastModified($file) <= $limit) {
                $disk->delete($file);
                $deleted++;
            }
        }

        foreach ($disk->directories(ExportUserData::DIRECTORY) as $directory) {
            if ($disk->allFiles($directory) === []) {
                $disk->deleteDirectory($directory);
            }
        }

        $this->info("{$deleted} export(s) deleted.");

        return self::SUCCESS;
    }
}
