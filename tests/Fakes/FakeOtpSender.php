<?php

namespace Tests\Fakes;

use App\Contracts\OtpSender;
use App\Enums\OtpPurpose;

class FakeOtpSender implements OtpSender
{
    /** @var list<array{phone: string, code: string, purpose: OtpPurpose, locale: string}> */
    public array $sent = [];

    public function send(string $phone, string $code, OtpPurpose $purpose, string $locale): void
    {
        $this->sent[] = compact('phone', 'code', 'purpose', 'locale');
    }

    public function lastCodeFor(string $phone): ?string
    {
        foreach (array_reverse($this->sent) as $message) {
            if ($message['phone'] === $phone) {
                return $message['code'];
            }
        }

        return null;
    }
}
