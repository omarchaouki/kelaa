<?php

namespace App\Actions\Moderation;

use App\Enums\ReplyStatus;
use App\Models\ReviewReply;
use App\Models\User;
use App\Notifications\ContentRejected;
use App\Notifications\ReplyToYourReview;
use App\Support\Moderation\ModerationGate;
use InvalidArgumentException;

class ModerateReply
{
    public function approve(User $moderator, ReviewReply $reply): void
    {
        $wasPending = $reply->status === ReplyStatus::Pending;
        $this->transition($moderator, $reply, ReplyStatus::Published, null, 'reply.approved');

        if ($wasPending) {
            $reply->loadMissing('review.user');
            $reply->review->user?->notify(new ReplyToYourReview($reply));
        }
    }

    public function reject(User $moderator, ReviewReply $reply, string $reason): void
    {
        $this->transition($moderator, $reply, ReplyStatus::Rejected, $reason, 'reply.rejected');
        $reply->loadMissing('user');
        $reply->user?->notify(new ContentRejected('reply', $reply->id, $reason));
    }

    public function hide(User $moderator, ReviewReply $reply, string $reason): void
    {
        $this->transition($moderator, $reply, ReplyStatus::HiddenByModeration, $reason, 'reply.hidden');
        $reply->loadMissing('user');
        $reply->user?->notify(new ContentRejected('reply', $reply->id, $reason));
    }

    private function transition(User $moderator, ReviewReply $reply, ReplyStatus $status, ?string $reason, string $event): void
    {
        $reason = $reason === null ? null : trim($reason);

        if ($status !== ReplyStatus::Published && ($reason === null || $reason === '')) {
            throw new InvalidArgumentException('A reason is required.');
        }

        ModerationGate::asModerator($moderator, function () use ($moderator, $reply, $status, $reason, $event): void {
            $previous = $reply->status;

            $reply->forceFill([
                'status' => $status,
                'moderated_by' => $moderator->id,
                'moderated_at' => now(),
                'moderation_reason' => $reason,
            ])->save();

            activity('moderation')
                ->performedOn($reply)
                ->causedBy($moderator)
                ->withProperties(['from' => $previous->value, 'to' => $status->value, 'reason' => $reason])
                ->log($event);
        });
    }
}
