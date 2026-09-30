<?php

namespace App\Services\Hours;

use Carbon\CarbonImmutable;

final readonly class OpeningStatus
{
    public function __construct(
        public bool $isOpen,
        public bool $alwaysOpen = false,
        public ?CarbonImmutable $closesAt = null,
        public ?CarbonImmutable $opensAt = null,
        public ?CarbonImmutable $now = null,
    ) {}

    public static function alwaysOpen(): self
    {
        return new self(isOpen: true, alwaysOpen: true);
    }

    /**
     * Short localized badge text: "Ouvert · ferme à 20:00", "Fermé · ouvre à 15:00"...
     */
    public function label(): string
    {
        if ($this->alwaysOpen) {
            return __('Ouvert 24h/24');
        }

        if ($this->isOpen) {
            return $this->closesAt === null
                ? __('Ouvert')
                : __('Ouvert · ferme à :time', ['time' => $this->closesAt->format('H:i')]);
        }

        if ($this->opensAt === null || $this->now === null) {
            return __('Fermé');
        }

        if ($this->opensAt->isSameDay($this->now)) {
            return __('Fermé · ouvre à :time', ['time' => $this->opensAt->format('H:i')]);
        }

        if ($this->opensAt->isSameDay($this->now->addDay())) {
            return __('Fermé · ouvre demain à :time', ['time' => $this->opensAt->format('H:i')]);
        }

        return __('Fermé · ouvre :day à :time', [
            'day' => $this->opensAt->locale(app()->getLocale())->dayName,
            'time' => $this->opensAt->format('H:i'),
        ]);
    }
}
