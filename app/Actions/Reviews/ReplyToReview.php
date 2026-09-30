<?php

namespace App\Actions\Reviews;

use App\Enums\ReplyStatus;
use App\Models\Review;
use App\Models\ReviewReply;
use App\Models\User;
use App\Notifications\ReplyToYourReview;
use App\Services\Moderation\BannedWordFilter;
use App\Support\Moderation\ModerationGate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * The business owner answers a review once; the answer can be edited.
 * Owners can reply or report, never change or remove the review itself.
 */
class ReplyToReview
{
    public const MIN_LENGTH = 2;

    public const MAX_LENGTH = 1500;

    public function __construct(
        private readonly BannedWordFilter $bannedWords,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function __invoke(User $owner, Review $review, string $body): ReviewReply
    {
        if (! $owner->can('reply', $review)) {
            throw new AuthorizationException(__('Seul le propriétaire de l’établissement peut répondre.'));
        }

        $body = SubmitReview::cleanBody($body) ?? '';
        $status = config('reviews.replies_premoderated') || $this->bannedWords->containsBannedWords($body)
            ? ReplyStatus::Pending
            : ReplyStatus::Published;

        return DB::transaction(function () use ($owner, $review, $body, $status): ReviewReply {
            $reply = ReviewReply::query()->where('review_id', $review->id)->lockForUpdate()->first();

            if ($reply === null) {
                $reply = new ReviewReply(['review_id' => $review->id, 'user_id' => $owner->id, 'body' => $body]);
                $reply->forceFill(['status' => $status])->save();

                if ($status === ReplyStatus::Published) {
                    $review->user?->notify(new ReplyToYourReview($reply));
                }

                return $reply;
            }

            $reply->fill(['body' => $body]);
            $reply->forceFill(['edited_at' => now()]);

            // An edit may need re-checking, but never lifts a moderation decision.
            $newStatus = match (true) {
                $reply->status === ReplyStatus::HiddenByModeration => ReplyStatus::HiddenByModeration,
                $status === ReplyStatus::Pending, $reply->status === ReplyStatus::Rejected => ReplyStatus::Pending,
                default => $reply->status,
            };

            ModerationGate::asSystem(fn () => $reply->forceFill(['status' => $newStatus])->save());

            return $reply;
        });
    }
}
