<?php

namespace App\Actions\Moderation;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\User;
use App\Notifications\ContentRejected;
use App\Support\Moderation\ModerationGate;
use InvalidArgumentException;

/**
 * Publishing and suspending listings. Paid plans never change these decisions.
 */
class ModerateBusiness
{
    public function publish(User $moderator, Business $business): void
    {
        $this->transition($moderator, $business, BusinessStatus::Published, null, 'business.published');
    }

    public function suspend(User $moderator, Business $business, string $reason): void
    {
        $this->transition($moderator, $business, BusinessStatus::Suspended, $reason, 'business.suspended');

        $business->loadMissing(['owner', 'submitter']);
        ($business->owner ?? $business->submitter)?->notify(new ContentRejected('business', $business->id, $reason));
    }

    private function transition(User $moderator, Business $business, BusinessStatus $status, ?string $reason, string $event): void
    {
        if ($status === BusinessStatus::Suspended && trim((string) $reason) === '') {
            throw new InvalidArgumentException('A reason is required.');
        }

        ModerationGate::asModerator($moderator, function () use ($moderator, $business, $status, $reason, $event): void {
            $previous = $business->status;

            $business->forceFill([
                'status' => $status,
                'published_at' => $status === BusinessStatus::Published ? ($business->published_at ?? now()) : $business->published_at,
            ])->save();

            // Suspended listings leave the search index; published ones enter it.
            $status === BusinessStatus::Published ? $business->searchable() : $business->unsearchable();

            activity('moderation')->performedOn($business)->causedBy($moderator)
                ->withProperties(['from' => $previous->value, 'to' => $status->value, 'reason' => $reason])
                ->log($event);
        });
    }
}
