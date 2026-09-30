<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ReplyStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';
    case HiddenByModeration = 'hidden_by_moderation';
}
