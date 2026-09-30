<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum BillingPeriod: string implements HasLabel
{
    use HasTranslatedLabel;

    case Monthly = 'monthly';
    case Yearly = 'yearly';
}
