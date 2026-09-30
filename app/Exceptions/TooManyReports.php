<?php

namespace App\Exceptions;

use RuntimeException;

class TooManyReports extends RuntimeException
{
    public function __construct(
        public readonly int $retryAfterSeconds,
    ) {
        parent::__construct('Too many reports.');
    }
}
