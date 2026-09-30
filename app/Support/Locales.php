<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class Locales
{
    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return array_keys(config('directory.locales'));
    }

    public static function default(): string
    {
        return config('directory.default_locale');
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && in_array($locale, self::supported(), true);
    }

    public static function direction(?string $locale = null): string
    {
        return config('directory.locales.'.($locale ?? app()->getLocale()).'.dir', 'ltr');
    }

    public static function isRtl(?string $locale = null): bool
    {
        return self::direction($locale) === 'rtl';
    }

    public static function nativeName(string $locale): string
    {
        return config("directory.locales.{$locale}.native", $locale);
    }

    /**
     * ICU locale used for number/date formatting (e.g. "ar_MA").
     */
    public static function regional(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return config("directory.locales.{$locale}.regional", $locale);
    }

    /**
     * Locale for a visitor landing on a URL without prefix.
     */
    public static function preferred(Request $request): string
    {
        $candidates = [
            // A URL such as /ar/account/... already says which language the visitor reads.
            $request->segment(1),
            $request->cookie('locale'),
            $request->user()?->locale,
            $request->getPreferredLanguage(self::supported()),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && self::isSupported($candidate)) {
                return $candidate;
            }
        }

        return self::default();
    }

    /**
     * The current URL translated to another locale (same route, same parameters, same query).
     */
    public static function switchUrl(Request $request, string $locale): string
    {
        $route = $request->route();

        if ($route !== null && $route->getName() !== null && in_array('locale', $route->parameterNames(), true)) {
            $url = URL::route($route->getName(), ['locale' => $locale] + $route->originalParameters());
            $query = $request->getQueryString();

            return $query ? "{$url}?{$query}" : $url;
        }

        return URL::to($locale);
    }

    /**
     * hreflang alternates for the current page.
     *
     * @return array<string, string>
     */
    public static function alternates(Request $request): array
    {
        $alternates = [];

        foreach (self::supported() as $locale) {
            $alternates[$locale] = self::switchUrl($request, $locale);
        }

        return $alternates;
    }
}
