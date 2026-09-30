<?php

namespace App\Services\Moderation;

use App\Models\BannedWord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Finds words from the admin-managed banned list in user text. Matching is
 * done on normalized whole words (case, Latin accents, Arabic diacritics and
 * tatweel, repeated letters), so "Arnaque", "arnâââque" and "ARNAQUE" match
 * the same entry. Multi-word entries match as phrases.
 */
class BannedWordFilter
{
    public const CACHE_KEY = 'moderation:banned-words';

    /**
     * @return list<string> The banned entries found (normalized).
     */
    public function matches(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $normalized = ' '.implode(' ', self::tokens($text)).' ';
        $found = [];

        foreach ($this->entries() as $entry) {
            if (str_contains($normalized, ' '.$entry.' ')) {
                $found[] = $entry;
            }
        }

        return $found;
    }

    public function containsBannedWords(?string $text): bool
    {
        return $this->matches($text) !== [];
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return list<string>
     */
    private function entries(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), fn () => BannedWord::query()
            ->pluck('word')
            ->map(fn (string $word) => implode(' ', self::tokens($word)))
            ->filter()
            ->unique()
            ->values()
            ->all());
    }

    /**
     * @return list<string>
     */
    public static function tokens(string $text): array
    {
        $text = mb_strtolower($text);
        // Arabic diacritics (tashkeel) and tatweel.
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $text) ?? '';

        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        return array_map(function (string $token): string {
            // Fold Latin accents only (keep Arabic letters intact).
            if (preg_match('/^[\p{Latin}\p{N}]+$/u', $token)) {
                $token = Str::ascii($token);
            }

            // "arnaaaque" => "arnaque"
            return preg_replace('/(.)\1{2,}/u', '$1', $token) ?? $token;
        }, $matches[0]);
    }
}
