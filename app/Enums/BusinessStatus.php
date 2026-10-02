<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum BusinessStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Published = 'published';
    case Suspended = 'suspended';

    /** A submitted listing the moderators declined (never published). */
    case Rejected = 'rejected';
}
