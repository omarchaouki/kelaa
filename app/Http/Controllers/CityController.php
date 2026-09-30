<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CityController extends Controller
{
    public function redirectToDefault(): RedirectResponse
    {
        $city = City::query()->active()->where('slug', config('directory.default_city'))->first()
            ?? City::query()->active()->orderBy('id')->firstOrFail();

        return redirect()->route('city.home', ['city' => $city]);
    }

    /**
     * City home. Placeholder until the directory (phase 2) is built.
     */
    public function show(City $city): View
    {
        abort_unless($city->is_active, 404);

        return view('city.home', ['city' => $city]);
    }
}
