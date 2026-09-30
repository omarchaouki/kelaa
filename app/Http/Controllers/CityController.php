<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\RedirectResponse;

class CityController extends Controller
{
    public function redirectToDefault(): RedirectResponse
    {
        $city = City::query()->active()->where('slug', config('directory.default_city'))->first()
            ?? City::query()->active()->orderBy('id')->firstOrFail();

        return redirect()->route('city.home', ['city' => $city]);
    }
}
