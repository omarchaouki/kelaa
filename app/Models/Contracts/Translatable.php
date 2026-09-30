<?php

namespace App\Models\Contracts;

/**
 * Models using spatie/laravel-translatable's HasTranslations trait.
 */
interface Translatable
{
    public function getTranslation(string $key, string $locale, bool $useFallbackLocale = true): mixed;

    /**
     * @param  array<int, string>|null  $allowedLocales
     * @return array<string, mixed>
     */
    public function getTranslations(?string $key = null, ?array $allowedLocales = null): array;
}
