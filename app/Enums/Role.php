<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    use HasTranslatedLabel;

    case Member = 'member';
    case BusinessOwner = 'business_owner';
    case Moderator = 'moderator';
    case Admin = 'admin';
}
