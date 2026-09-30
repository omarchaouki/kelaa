<?php

namespace App\Observers;

use App\Services\Moderation\BannedWordFilter;

class BannedWordObserver
{
    public function saved(): void
    {
        BannedWordFilter::flush();
    }

    public function deleted(): void
    {
        BannedWordFilter::flush();
    }
}
