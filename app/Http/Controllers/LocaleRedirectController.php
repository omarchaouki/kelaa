<?php

namespace App\Http\Controllers;

use App\Support\Locales;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route('locale.home', ['locale' => Locales::preferred($request)]);
    }
}
