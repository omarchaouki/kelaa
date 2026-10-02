<?php

namespace App\Actions\Moderation;

use App\Enums\BusinessStatus;
use App\Models\Business;
use App\Models\User;
use App\Notifications\BusinessRequestDecided;
use App\Notifications\ContentRejected;
use App\Support\Moderation\ModerationGate;
use InvalidArgumentException;

/**
 * Publishing, declining and suspending listings. Paid plans never change these decisions.
 */
class ModerateBusiness
{
    public function publish(User $moderator, Business $business): void
    {
        $wasRequest = in_array($business->status, [BusinessStatus::Pending, BusinessStatus::Rejected], true);

        $this->transition($moderator, $business, BusinessStatus::Published, null, 'business.published');

        // A member's submission went live: tell them.
        if ($wasRequest) {
            $business->loadMissing(['submitter', 'city']);
            $business->submitter?->notify(new BusinessRequestDecided(
                $business->id,
                $business->name,
                published: true,
                url: route('city.business', ['locale' => $business->submitter->locale, 'city' => $business->city, 'business' => $business]),
            ));
        }
    }

    /**
     * Declines a submitted listing that was never published.
     */
    public function decline(User $moderator, Business $business, string $reason): void
    {
        if ($business->status !== BusinessStatus::Pending) {
            throw new InvalidArgumentException('Only pending submissions can be declined.');
        }

        $this->transition($moderator, $business, BusinessStatus::Rejected, $reason, 'business.declined');

        $business->loadMissing('submitter');
        $business->submitter?->notify(new BusinessRequestDecided($business->id, $business->name, published: false, reason: $reason));
    }

    public function suspend(User $moderator, Business $business, string $reason): void
    {
        $this->transition($moderator, $business, BusinessStatus::Suspended, $reason, 'business.suspended');

        $business->loadMissing(['owner', 'submitter']);
        ($business->owner ?? $business->submitter)?->notify(new ContentRejected('business', $business->id, $reason));
    }

    private function transition(User $moderator, Business $business, BusinessStatus $status, ?string $reason, string $event): void
    {
        if (in_array($status, [BusinessStatus::Suspended, BusinessStatus::Rejected], true) && trim((string) $reason) === '') {
            throw new InvalidArgumentException('A reason is required.');
        }

        ModerationGate::asModerator($moderator, function () use ($moderator, $business, $status, $reason, $event): void {
            $previous = $business->status;

            $business->forceFill([
                'status' => $status,
                'rejection_reason' => $status === BusinessStatus::Rejected ? mb_substr(trim((string) $reason), 0, 500) : null,
                'published_at' => $status === BusinessStatus::Published ? ($business->published_at ?? now()) : $business->published_at,
            ])->save();

            // Only published listings are in the search index.
            $status === BusinessStatus::Published ? $business->searchable() : $business->unsearchable();

            activity('moderation')->performedOn($business)->causedBy($moderator)
                ->withProperties(['from' => $previous->value, 'to' => $status->value, 'reason' => $reason])
                ->log($event);
        });
    }
}
