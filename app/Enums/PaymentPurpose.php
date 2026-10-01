<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum PaymentPurpose: string implements HasLabel
{
    use HasTranslatedLabel;

    case Subscription = 'subscription';
    case Placement = 'placement';
}
