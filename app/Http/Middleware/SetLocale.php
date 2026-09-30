<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the {locale} route prefix. Registered as persistent Livewire
 * middleware so component updates keep the page's locale.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        $locale = $route?->parameter('locale');

        if (! is_string($locale) || ! Locales::isSupported($locale)) {
            abort(404);
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        URL::defaults(['locale' => $locale]);

        // Controllers and components never need the locale as an argument.
        $route->forgetParameter('locale');

        if ($request->cookie('locale') !== $locale) {
            Cookie::queue(Cookie::make('locale', $locale, 60 * 24 * 365, httpOnly: true, sameSite: 'lax'));
        }

        return $next($request);
    }
}
