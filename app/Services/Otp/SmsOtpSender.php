<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryFailed;

/**
 * SMS driver. The provider is not chosen yet: plug its HTTP API in send()
 * using config('otp.drivers.sms').
 */
class SmsOtpSender implements OtpSender
{
    /**
     * @param  array{provider?: string|null, api_key?: string|null, sender?: string|null}  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function send(string $phone, string $code, OtpPurpose $purpose, string $locale): void
    {
        if (empty($this->config['provider']) || empty($this->config['api_key'])) {
            throw new OtpDeliveryFailed('SMS OTP driver is not configured (OTP_SMS_PROVIDER / OTP_SMS_API_KEY).');
        }

        throw new OtpDeliveryFailed("SMS provider [{$this->config['provider']}] is not implemented yet.");
    }
}
