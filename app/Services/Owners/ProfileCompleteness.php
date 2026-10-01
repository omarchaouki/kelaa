<?php

namespace App\Services\Owners;

use App\Models\Business;

/**
 * 0-100 score of how complete a listing is (a ranking signal, and a
 * checklist for owners). Only information visitors benefit from counts.
 */
class ProfileCompleteness
{
    /**
     * Item => weight. Weights add up to 100.
     */
    public const ITEMS = [
        'description' => 15,
        'phone' => 10,
        'whatsapp' => 15,
        'hours' => 15,
        'photos' => 15,
        'address' => 10,
        'neighborhood' => 5,
        'price_range' => 5,
        'website_or_social' => 5,
        'logo' => 5,
    ];

    /**
     * @return array<string, bool>
     */
    public function checklist(Business $business): array
    {
        $business->loadMissing(['hours', 'media']);

        return [
            'description' => mb_strlen(trim((string) $business->getTranslation('description', 'fr', false)).trim((string) $business->getTranslation('description', 'ar', false))) >= 40,
            'phone' => $business->phone !== null,
            'whatsapp' => $business->whatsapp !== null,
            'hours' => $business->is_always_open || $business->hours->contains(fn ($hour) => ! $hour->is_closed),
            'photos' => $business->getMedia('gallery')->count() >= 3,
            'address' => trim((string) $business->address) !== '',
            'neighborhood' => $business->neighborhood_id !== null,
            'price_range' => $business->price_range !== null,
            'website_or_social' => $business->website !== null || ! empty($business->social_links),
            'logo' => $business->getMedia('logo')->isNotEmpty(),
        ];
    }

    public function score(Business $business): int
    {
        $score = 0;

        foreach ($this->checklist($business) as $item => $done) {
            $score += $done ? self::ITEMS[$item] : 0;
        }

        return min(100, $score);
    }

    public function refresh(Business $business): int
    {
        $score = $this->score($business);

        // Ranking columns are not mass assignable: query builder update, no model events.
        Business::query()->whereKey($business->id)->toBase()->update(['profile_completeness' => $score]);
        $business->setRawAttributes(['profile_completeness' => $score] + $business->getAttributes(), true);

        return $score;
    }
}
