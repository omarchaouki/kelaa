<?php

namespace App\Observers;

use App\Support\DirectoryCache;

/**
 * Attached to every model whose changes alter directory pages.
 */
class FlushDirectoryCache
{
    public function saved(): void
    {
        DirectoryCache::flush();
    }

    public function deleted(): void
    {
        DirectoryCache::flush();
    }

    public function restored(): void
    {
        DirectoryCache::flush();
    }
}
