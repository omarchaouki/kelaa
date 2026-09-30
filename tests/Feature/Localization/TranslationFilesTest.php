<?php

use App\Support\Locales;

it('has the same app strings in every locale', function () {
    $keys = fn (string $locale) => array_keys(json_decode(file_get_contents(lang_path("app/{$locale}.json")), true, flags: JSON_THROW_ON_ERROR));

    $reference = $keys('fr');
    sort($reference);

    foreach (Locales::supported() as $locale) {
        $localeKeys = $keys($locale);
        sort($localeKeys);

        expect($localeKeys)->toBe($reference, "lang/app/{$locale}.json is out of sync with fr.json");
    }
});

it('translates every enum case in every locale', function () {
    $enums = collect(glob(app_path('Enums/*.php')))
        ->map(fn (string $file) => 'App\\Enums\\'.basename($file, '.php'))
        ->filter(fn (string $class) => enum_exists($class) && method_exists($class, 'getLabel'));

    // Without a fallback, a missing label shows up as its raw key.
    app('translator')->setFallback('none');

    foreach (Locales::supported() as $locale) {
        app()->setLocale($locale);

        foreach ($enums as $enum) {
            foreach ($enum::cases() as $case) {
                expect($case->getLabel())->not->toStartWith('enums.', "{$enum}::{$case->name} has no {$locale} label");
            }
        }
    }
});
