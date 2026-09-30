<?php

namespace App\Actions\Reviews;

use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class ToggleHelpfulVote
{
    /**
     * @return bool Whether the user now finds the review helpful.
     *
     * @throws AuthorizationException
     */
    public function __invoke(User $user, Review $review): bool
    {
        if (! $user->can('vote', $review)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($user, $review): bool {
            $deleted = ReviewVote::query()->where('review_id', $review->id)->where('user_id', $user->id)->delete();

            if ($deleted === 0) {
                try {
                    ReviewVote::query()->create(['review_id' => $review->id, 'user_id' => $user->id]);
                } catch (UniqueConstraintViolationException) {
                    // Double click: the other request already voted.
                }
            }

            // Recount instead of increment: always consistent, never negative.
            Review::query()->whereKey($review->id)->toBase()->update([
                'helpful_count' => ReviewVote::query()->where('review_id', $review->id)->count(),
            ]);

            return $deleted === 0;
        });
    }
}
