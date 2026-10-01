<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum OtpPurpose: string implements HasLabel
{
    use HasTranslatedLabel;

    case Login = 'login';
    case BusinessClaim = 'business_claim';
    case PhoneVerification = 'phone_verification';
    case AccountDeletion = 'account_deletion';
}
