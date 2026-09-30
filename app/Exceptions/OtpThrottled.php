<?php

namespace App\Exceptions;

use RuntimeException;

class OtpThrottled extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct("Too many OTP attempts. Retry in {$retryAfterSeconds} seconds.");
    }
}
