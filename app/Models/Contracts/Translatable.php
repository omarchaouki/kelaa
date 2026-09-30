<?php

namespace App\Models\Contracts;

/**
 * Models using spatie/laravel-translatable's HasTranslations trait.
 */
interface Translatable
{
    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true): mixed;
}
