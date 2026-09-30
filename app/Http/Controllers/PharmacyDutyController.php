<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Services\Directory\CityDirectory;
use Illuminate\Contracts\View\View;

class PharmacyDutyController extends Controller
{
    public function __invoke(City $city, CityDirectory $directory): View
    {
        abort_unless($city->is_active, 404);

        $duties = $directory->pharmaciesOnDuty($city, hoursAhead: 24 * 7);
        $now = now();

        return view('directory.pharmacies', [
            'city' => $city,
            'current' => $duties->filter(fn ($duty) => $duty->starts_at->lessThanOrEqualTo($now)),
            'upcoming' => $duties->filter(fn ($duty) => $duty->starts_at->greaterThan($now))
                ->groupBy(fn ($duty) => $duty->date->toDateString()),
        ]);
    }
}
