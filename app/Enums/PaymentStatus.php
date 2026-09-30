<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Validated = 'validated';
    case Rejected = 'rejected';
    case Refunded = 'refunded';
}
