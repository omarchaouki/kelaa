<?php

namespace App\Contracts;

use App\Enums\OtpPurpose;

interface OtpSender
{
    /**
     * Deliver a one-time code to an E.164 Moroccan phone number.
     *
     * @throws \App\Exceptions\OtpDeliveryFailed
     */
    public function send(string $phone, string $code, OtpPurpose $purpose, string $locale): void;
}
