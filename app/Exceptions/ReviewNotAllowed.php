<?php

namespace App\Exceptions;

use App\Services\Reviews\ReviewDenial;
use RuntimeException;

class ReviewNotAllowed extends RuntimeException
{
    public function __construct(
        public readonly ReviewDenial $denial,
    ) {
        parent::__construct($denial->message());
    }
}
