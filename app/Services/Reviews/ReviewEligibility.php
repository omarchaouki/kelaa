<?php

namespace App\Services\Reviews;

use App\Models\Business;
use App\Models\Review;
use App\Models\User;

/**
 * Who may review a business (spec §6 Reviews, §11).
 */
class ReviewEligibility
{
    /**
     * Null when allowed. Editing one's existing review skips the daily limit.
     */
    public function denialFor(?User $user, Business $business): ?ReviewDenial
    {
        if ($user === null) {
            return ReviewDenial::Guest;
        }

        if (! $business->isPublished()) {
            return ReviewDenial::BusinessUnavailable;
        }

        if ($user->isBanned()) {
            return ReviewDenial::Banned;
        }

        if (! $user->hasVerifiedPhone()) {
            return ReviewDenial::PhoneNotVerified;
        }

        if ($business->owner_id === $user->id) {
            return ReviewDenial::OwnBusiness;
        }

        if ($this->isCompetitor($user, $business)) {
            return ReviewDenial::ConflictOfInterest;
        }

        $hasReview = Review::query()->where('business_id', $business->id)->where('user_id', $user->id)->exists();

        if (! $hasReview && $this->reachedDailyLimit($user)) {
            return ReviewDenial::DailyLimit;
        }

        return null;
    }

    /**
     * The user owns a business of the same primary category in the same city.
     */
    public function isCompetitor(User $user, Business $business): bool
    {
        $primaryCategoryId = $business->categories()->wherePivot('is_primary', true)->value('categories.id');

        if ($primaryCategoryId === null) {
            return false;
        }

        return Business::query()
            ->where('owner_id', $user->id)
            ->where('city_id', $business->city_id)
            ->whereKeyNot($business->id)
            ->whereHas('categories', fn ($q) => $q->whereKey($primaryCategoryId)->where('business_category.is_primary', true))
            ->exists();
    }

    public function reachedDailyLimit(User $user): bool
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count() >= (int) config('reviews.daily_limit');
    }
}
