<?php

use Symfony\Component\Finder\Finder;

/**
 * Every French source string used with __() / trans_choice() in the public
 * app must exist in the English and Arabic JSON files.
 *
 * @return list<string>
 */
function sourceTranslationKeys(): array
{
    $finder = (new Finder)->files()
        ->in([app_path(), resource_path('views')])
        ->exclude(['Filament', 'Console', 'Services/Pharmacies'])  // admin panel, CLI and admin-only imports stay in French
        ->name(['*.php']);

    $keys = [];

    foreach ($finder as $file) {
        preg_match_all("/(?:__|trans_choice)\(\s*'((?:[^'\\\\]|\\\\.)+)'/u", $file->getContents(), $matches);

        foreach ($matches[1] as $key) {
            $key = str_replace("\\'", "'", $key);

            // Group keys ("pages.cgu", "enums.x.y", "auth.failed") live in PHP files.
            if (preg_match('/^[a-z_-]+\.[a-z0-9_.-]+$/', $key) || str_contains($key, '{$')) {
                continue;
            }

            $keys[$key] = true;
        }
    }

    $keys = array_keys($keys);
    sort($keys);

    return $keys;
}

it('translates every public string in English and Arabic', function (string $locale) {
    $translations = json_decode(file_get_contents(lang_path("app/{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);

    $missing = array_values(array_filter(sourceTranslationKeys(), fn (string $key) => ! array_key_exists($key, $translations)));

    expect($missing)->toBe([], "Missing in lang/app/{$locale}.json:\n".implode("\n", $missing));
})->with(['en', 'ar']);
