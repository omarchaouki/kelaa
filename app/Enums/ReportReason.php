<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ReportReason: string implements HasLabel
{
    use HasTranslatedLabel;

    case Spam = 'spam';
    case Fake = 'fake';
    case Offensive = 'offensive';
    case ConflictOfInterest = 'conflict_of_interest';
    case WrongInformation = 'wrong_information';
    case ClosedBusiness = 'closed_business';
    case InappropriatePhoto = 'inappropriate_photo';
    case Other = 'other';
}
