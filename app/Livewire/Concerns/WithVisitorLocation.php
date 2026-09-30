<?php

namespace App\Livewire\Concerns;

use App\Support\Geo\Coordinates;
use App\Support\VisitorLocation;

/**
 * "Use my location" for Livewire pages. Coordinates travel once from the
 * browser, are validated and rounded, then live only in the session.
 */
trait WithVisitorLocation
{
    public function setLocation(mixed $latitude, mixed $longitude): void
    {
        if (VisitorLocation::set($latitude, $longitude) === null) {
            $this->addError('location', __('Position invalide.'));

            return;
        }

        $this->locationChanged();
    }

    public function forgetLocation(): void
    {
        VisitorLocation::forget();
        $this->locationChanged();
    }

    protected function location(): ?Coordinates
    {
        return VisitorLocation::get();
    }

    protected function locationChanged(): void
    {
        //
    }
}
