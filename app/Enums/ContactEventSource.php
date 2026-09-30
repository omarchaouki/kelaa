<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ContactEventSource: string implements HasLabel
{
    use HasTranslatedLabel;

    case Organic = 'organic';
    case Sponsored = 'sponsored';
    case Search = 'search';
    case Map = 'map';
}
