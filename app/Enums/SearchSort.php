<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum SearchSort: string implements HasLabel
{
    use HasTranslatedLabel;

    case Relevance = 'relevance';
    case Rating = 'rating';
    case Distance = 'distance';
    case Reviews = 'reviews';
    case Newest = 'newest';
}
