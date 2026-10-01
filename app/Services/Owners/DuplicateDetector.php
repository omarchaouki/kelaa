<?php

namespace App\Services\Owners;

use App\Models\Business;
use App\Support\Geo\Coordinates;
use App\Support\Phone\MoroccanPhone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Finds listings a new submission may duplicate: same phone or WhatsApp
 * number anywhere in the city, or a similar name within 200 m.
 */
class DuplicateDetector
{
    /**
     * Generic words that say nothing about which business it is.
     */
    private const STOP_WORDS = [
        'chez', 'le', 'la', 'les', 'de', 'du', 'des', 'et', 'al', 'el', 'dar',
        'garage', 'atelier', 'pharmacie', 'cafe', 'snack', 'restaurant', 'salon', 'boulangerie',
        'patisserie', 'clinique', 'cabinet', 'espace', 'centre', 'societe', 'ste', 'sarl', 'auto',
    ];

    /**
     * @return Collection<int, Business>
     */
    public function find(int $cityId, string $name, ?Coordinates $location, ?string $phone, ?string $whatsapp, ?int $ignoreId = null): Collection
    {
        $phones = array_values(array_filter([
            MoroccanPhone::normalize($phone, mobileOnly: false),
            MoroccanPhone::normalize($whatsapp),
        ]));

        $byPhone = $phones === [] ? new Collection : Business::query()
            ->where('city_id', $cityId)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where(fn ($q) => $q->whereIn('phone', $phones)->orWhereIn('whatsapp', $phones))
            ->limit(10)
            ->get();

        $byName = new Collection;

        if ($location !== null) {
            $needle = self::normalizeName($name);

            $byName = Business::query()
                ->where('city_id', $cityId)
                ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
                ->withinDistance($location, (float) config('owners.duplicate_radius_m'))
                ->limit(50)
                ->get()
                ->filter(fn (Business $candidate) => self::similarity($needle, self::normalizeName($candidate->name)) >= (int) config('owners.duplicate_name_similarity'));
        }

        return $byPhone->merge($byName)->unique('id')->values();
    }

    public static function normalizeName(string $name): string
    {
        $words = preg_split('/[^a-z0-9\p{Arabic}]+/u', Str::lower(Str::ascii($name)) ?: '', flags: PREG_SPLIT_NO_EMPTY) ?: [];
        $meaningful = array_values(array_diff($words, self::STOP_WORDS));

        return implode(' ', $meaningful === [] ? $words : $meaningful);
    }

    /**
     * 0-100.
     */
    public static function similarity(string $a, string $b): int
    {
        if ($a === '' || $b === '') {
            return 0;
        }

        if ($a === $b) {
            return 100;
        }

        // "amine" inside "amine plomberie" is a strong signal; "a" inside anything is not.
        if (min(mb_strlen($a), mb_strlen($b)) >= 4 && (str_contains($a, $b) || str_contains($b, $a))) {
            return 100;
        }

        similar_text($a, $b, $percent);

        return (int) round($percent);
    }
}
