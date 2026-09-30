<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum EditSuggestionStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
