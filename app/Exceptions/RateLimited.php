<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\RateLimiter;
use RuntimeException;

class RateLimited extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Too many attempts.');
    }

    /**
     * Counts one attempt, or throws when the limit is reached.
     *
     * @throws self
     */
    public static function guard(string $key, int $maxAttempts, int $decaySeconds): void
    {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw new self(RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    public function minutes(): int
    {
        return (int) ceil($this->retryAfterSeconds / 60);
    }
}
