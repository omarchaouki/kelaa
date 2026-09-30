<?php

namespace App\Actions\Moderation;

use App\Enums\ReviewStatus;
use App\Jobs\RecalculateBusinessRating;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ContentRejected;
use App\Notifications\NewReviewOnYourBusiness;
use App\Support\Moderation\ModerationGate;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moderator decisions on reviews. Every decision is logged (who, when, why)
 * and rejections/hides are explained to the author.
 */
class ModerateReview
{
    public function approve(User $moderator, Review $review): void
    {
        $firstPublication = $review->published_at === null;

        $this->transition($moderator, $review, ReviewStatus::Published, null, 'review.approved');

        if ($firstPublication) {
            $review->loadMissing('business.owner');
            $review->business->owner?->notify(new NewReviewOnYourBusiness($review));
        }
    }

    public function reject(User $moderator, Review $review, string $reason): void
    {
        $this->transition($moderator, $review, ReviewStatus::Rejected, $reason, 'review.rejected');
        $review->loadMissing('user');
        $review->user?->notify(new ContentRejected('review', $review->id, $reason));
    }

    public function hide(User $moderator, Review $review, string $reason): void
    {
        $this->transition($moderator, $review, ReviewStatus::HiddenByModeration, $reason, 'review.hidden');
        $review->loadMissing('user');
        $review->user?->notify(new ContentRejected('review', $review->id, $reason));
    }

    private function transition(User $moderator, Review $review, ReviewStatus $status, ?string $reason, string $event): void
    {
        $reason = $reason === null ? null : trim($reason);

        if ($status !== ReviewStatus::Published && ($reason === null || $reason === '')) {
            throw new InvalidArgumentException('A reason is required.');
        }

        ModerationGate::asModerator($moderator, function () use ($moderator, $review, $status, $reason, $event): void {
            DB::transaction(function () use ($moderator, $review, $status, $reason, $event): void {
                $previous = $review->status;

                $review->forceFill([
                    'status' => $status,
                    'published_at' => $status === ReviewStatus::Published ? ($review->published_at ?? now()) : $review->published_at,
                    'moderated_by' => $moderator->id,
                    'moderated_at' => now(),
                    'moderation_reason' => $reason,
                ])->save();

                activity('moderation')
                    ->performedOn($review)
                    ->causedBy($moderator)
                    ->withProperties(['from' => $previous->value, 'to' => $status->value, 'reason' => $reason])
                    ->log($event);
            });
        });

        RecalculateBusinessRating::dispatch($review->business_id)->afterCommit();
    }
}
