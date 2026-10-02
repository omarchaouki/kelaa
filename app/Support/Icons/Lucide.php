<?php

namespace App\Support\Icons;

use Illuminate\Support\HtmlString;

/**
 * Inner markup of Lucide icons, read once per request. Pages such as the
 * city home draw hundreds of category icons: going through Blade's dynamic
 * components and a file check for each one made them take seconds.
 */
final class Lucide
{
    private const DIRECTORY = 'vendor/mallardduck/blade-lucide-icons/resources/svg/icons';

    /** @var array<string, string|null> */
    private static array $cache = [];

    /**
     * Paths of the icon, or null when the name is invalid or unknown. Names
     * come from the database: only plain Lucide names are accepted, so a
     * crafted value can never point outside the icon directory.
     */
    public static function inner(?string $name): ?string
    {
        if (! is_string($name) || ! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $name)) {
            return null;
        }

        if (! array_key_exists($name, self::$cache)) {
            $path = base_path(self::DIRECTORY."/{$name}.svg");
            $svg = is_file($path) ? (string) file_get_contents($path) : '';

            self::$cache[$name] = preg_match('#<svg[^>]*>(.*)</svg>#s', $svg, $match) === 1 ? trim($match[1]) : null;
        }

        return self::$cache[$name];
    }

    /**
     * Complete <svg> for hot loops (hundreds of icons), without a Blade
     * component per icon. Unknown names fall back to the "store" icon.
     */
    public static function svg(?string $name, int $size = 24, string $class = ''): HtmlString
    {
        $inner = self::inner($name) ?? self::inner('store') ?? '';
        $classAttribute = $class === '' ? '' : ' class="'.e($class).'"';

        return new HtmlString(
            '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"'.$classAttribute.'>'.$inner.'</svg>'
        );
    }
}
