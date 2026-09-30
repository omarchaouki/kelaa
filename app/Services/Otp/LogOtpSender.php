<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use App\Enums\OtpPurpose;
use Illuminate\Support\Facades\Log;

/**
 * Development driver: the code is written to the application log.
 */
class LogOtpSender implements OtpSender
{
    public function send(string $phone, string $code, OtpPurpose $purpose, string $locale): void
    {
        Log::info("OTP for {$phone} ({$purpose->value}): {$code}");
    }
}
