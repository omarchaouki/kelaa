<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Review;
use App\Models\User;

/**
 * Public abilities (reply, vote) and admin panel abilities (moderation).
 * Nobody deletes or edits someone else's review: moderators hide it with a
 * reason, owners can only reply or report. Paying for a plan changes nothing here.
 */
class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->moderates($user);
    }

    public function view(User $user, Review $review): bool
    {
        return $this->moderates($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Review $review): bool
    {
        return false;
    }

    public function moderate(User $user, Review $review): bool
    {
        return $this->moderates($user);
    }

    public function delete(User $user, Review $review): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function reply(User $user, Review $review): bool
    {
        $review->loadMissing('business');

        return ! $user->isBanned()
            && $review->isPublished()
            && $review->business->owner_id === $user->id;
    }

    public function vote(User $user, Review $review): bool
    {
        return ! $user->isBanned()
            && $review->isPublished()
            && $review->user_id !== $user->id;
    }

    private function moderates(User $user): bool
    {
        return ! $user->isBanned() && $user->can(Permission::ModerateReviews->value);
    }
}
