<?php

namespace App\Services\Hours;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class OpeningSlot
{
    public function __construct(
        public CarbonImmutable $opensAt,
        public CarbonImmutable $closesAt,
    ) {}

    public function contains(CarbonInterface $moment): bool
    {
        return $moment->greaterThanOrEqualTo($this->opensAt) && $moment->lessThan($this->closesAt);
    }
}
