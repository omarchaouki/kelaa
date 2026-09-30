<?php

namespace App\Support\Phone;

/**
 * Moroccan phone numbers, stored in E.164 (+212XXXXXXXXX).
 *
 * Mobile numbers start with 06 or 07 locally (+2126 / +2127).
 * Landlines start with 05 (+2125) and are accepted for businesses only.
 */
final class MoroccanPhone
{
    /**
     * Normalize any common way of typing a Moroccan number to E.164, or null if invalid.
     * Accepts "06 12 34 56 78", "0612345678", "+212 6-12-34-56-78", "00212612345678", "212612345678".
     */
    public static function normalize(?string $input, bool $mobileOnly = true): ?string
    {
        if ($input === null) {
            return null;
        }

        // Arabic-Indic and Eastern Arabic-Indic digits typed on Arabic keyboards.
        $input = strtr($input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $hasPlus = str_starts_with(trim($input), '+');
        $digits = preg_replace('/\D+/', '', $input) ?? '';

        $national = match (true) {
            $hasPlus && str_starts_with($digits, '212') => substr($digits, 3),
            str_starts_with($digits, '00212') => substr($digits, 5),
            strlen($digits) === 12 && str_starts_with($digits, '212') => substr($digits, 3),
            strlen($digits) === 10 && str_starts_with($digits, '0') => substr($digits, 1),
            default => null,
        };

        // Tolerate "+212 06..." (trunk zero kept after the country code).
        if ($national !== null && strlen($national) === 10 && str_starts_with($national, '0')) {
            $national = substr($national, 1);
        }

        if ($national === null || strlen($national) !== 9) {
            return null;
        }

        $allowedPrefixes = $mobileOnly ? ['6', '7'] : ['5', '6', '7', '8'];

        if (! in_array($national[0], $allowedPrefixes, true)) {
            return null;
        }

        return '+212'.$national;
    }

    public static function isMobile(?string $input): bool
    {
        return self::normalize($input) !== null;
    }

    /**
     * "+212612345678" => "06 12 34 56 78"
     */
    public static function formatNational(string $e164): string
    {
        $national = '0'.substr($e164, 4);

        return trim(chunk_split($national, 2, ' '));
    }

    /**
     * Digits only, as expected by wa.me links: "212612345678".
     */
    public static function forWhatsApp(string $e164): string
    {
        return ltrim($e164, '+');
    }
}
