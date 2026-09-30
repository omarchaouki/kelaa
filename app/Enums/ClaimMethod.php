<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ClaimMethod: string implements HasLabel
{
    use HasTranslatedLabel;

    case PhoneOtpToBusinessNumber = 'phone_otp_to_business_number';
    case DocumentUpload = 'document_upload';
    case Manual = 'manual';
}
