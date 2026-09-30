<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Open = 'open';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';
}
