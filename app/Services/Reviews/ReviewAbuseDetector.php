<?php

namespace App\Services\Reviews;

use App\Models\Business;
use App\Models\Review;
use App\Models\User;
use App\Services\Moderation\BannedWordFilter;

/**
 * Abuse rules (spec §11). Any flag sends the review to moderation instead
 * of publishing it; the flags are stored for the moderator.
 */
class ReviewAbuseDetector
{
    public const NEW_ACCOUNT = 'new_account';

    public const SAME_IP = 'same_ip';

    public const SAME_DEVICE = 'same_device';

    public const BURST = 'burst';

    public const OWNER_PHONE = 'reviewer_phone_matches_business';

    public const BANNED_WORDS = 'banned_words';

    public function __construct(
        private readonly BannedWordFilter $bannedWords,
    ) {}

    /**
     * @return list<string>
     */
    public function flags(User $author, Business $business, ?string $body, string $ipHash, string $deviceHash, ?Review $existing = null): array
    {
        $flags = [];
        $others = Review::query()
            ->where('business_id', $business->id)
            ->when($existing, fn ($q) => $q->whereKeyNot($existing->id))
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $author->id));

        if ($author->created_at === null || $author->created_at->greaterThan(now()->subHours((int) config('reviews.abuse.new_account_hours')))) {
            $flags[] = self::NEW_ACCOUNT;
        }

        if ((clone $others)->where('ip_hash', $ipHash)->exists()) {
            $flags[] = self::SAME_IP;
        }

        if ((clone $others)->where('device_hash', $deviceHash)->exists()) {
            $flags[] = self::SAME_DEVICE;
        }

        $recent = (clone $others)->where('created_at', '>=', now()->subHours((int) config('reviews.abuse.burst_hours')))->count();

        if ($recent >= (int) config('reviews.abuse.burst_count')) {
            $flags[] = self::BURST;
        }

        if ($author->phone !== null && in_array($author->phone, array_filter([$business->phone, $business->whatsapp]), true)) {
            $flags[] = self::OWNER_PHONE;
        }

        if ($this->bannedWords->containsBannedWords($body)) {
            $flags[] = self::BANNED_WORDS;
        }

        return $flags;
    }
}
