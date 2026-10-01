<?php

namespace App\Actions\Moderation;

use App\Actions\Owners\ApproveClaim;
use App\Enums\ClaimStatus;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Notifications\ClaimDecided;
use App\Support\Moderation\ModerationGate;
use InvalidArgumentException;

class ModerateClaim
{
    public function __construct(
        private readonly ApproveClaim $approveClaim,
    ) {}

    public function approve(User $moderator, BusinessClaim $claim): void
    {
        ModerationGate::asModerator($moderator, fn () => ($this->approveClaim)($claim, $moderator));
    }

    public function reject(User $moderator, BusinessClaim $claim, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required.');
        }

        ModerationGate::asModerator($moderator, function () use ($moderator, $claim, $reason): void {
            $claim->forceFill([
                'status' => ClaimStatus::Rejected,
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
                'rejection_reason' => mb_substr($reason, 0, 255),
            ])->save();

            activity('moderation')->performedOn($claim)->causedBy($moderator)
                ->withProperties(['reason' => $reason])->log('claim.rejected');
        });

        $claim->loadMissing('user');
        $claim->user->notify(new ClaimDecided($claim, approved: false, reason: $reason));
    }
}
