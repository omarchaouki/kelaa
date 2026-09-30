<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PlacementStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}
