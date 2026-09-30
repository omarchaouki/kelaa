<?php

namespace App\Services\Otp;

use App\Contracts\OtpSender;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryFailed;

/**
 * WhatsApp Business driver (authentication template message). The provider
 * is not chosen yet: plug its API in send() using config('otp.drivers.whatsapp').
 */
class WhatsAppOtpSender implements OtpSender
{
    /**
     * @param  array{provider?: string|null, api_key?: string|null, phone_number_id?: string|null, template?: string|null}  $config
     */
    public function __construct(
        private readonly array $config,
    ) {}

    public function send(string $phone, string $code, OtpPurpose $purpose, string $locale): void
    {
        if (empty($this->config['provider']) || empty($this->config['api_key'])) {
            throw new OtpDeliveryFailed('WhatsApp OTP driver is not configured (OTP_WHATSAPP_PROVIDER / OTP_WHATSAPP_API_KEY).');
        }

        throw new OtpDeliveryFailed("WhatsApp provider [{$this->config['provider']}] is not implemented yet.");
    }
}
