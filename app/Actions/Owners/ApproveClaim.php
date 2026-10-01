<?php

namespace App\Actions\Owners;

use App\Enums\ClaimStatus;
use App\Enums\Role;
use App\Exceptions\UnauthorizedStatusChange;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\User;
use App\Notifications\ClaimDecided;
use App\Support\Moderation\ModerationGate;
use Illuminate\Support\Facades\DB;

/**
 * Hands a listing to the claimant: owner set, business_owner role granted,
 * competing pending claims closed. Only a moderator, or the system after a
 * successful OTP to the listing's own number, may do it.
 */
class ApproveClaim
{
    public function __invoke(BusinessClaim $claim, ?User $moderator): void
    {
        if (! ModerationGate::allowsStatusChange()) {
            throw UnauthorizedStatusChange::for('claim');
        }

        DB::transaction(function () use ($claim, $moderator): void {
            $business = Business::query()->whereKey($claim->business_id)->lockForUpdate()->firstOrFail();

            if ($business->owner_id !== null && $business->owner_id !== $claim->user_id) {
                throw UnauthorizedStatusChange::for('claim of an owned business');
            }

            $claim->forceFill([
                'status' => ClaimStatus::Approved,
                'reviewed_by' => $moderator?->id,
                'reviewed_at' => now(),
            ])->save();

            $business->forceFill(['owner_id' => $claim->user_id, 'is_claimed' => true, 'last_activity_at' => now()])->save();

            $claim->loadMissing('user');
            $claim->user->assignRole(Role::BusinessOwner->value);

            // Other pending claims on this listing are closed.
            BusinessClaim::query()
                ->where('business_id', $business->id)
                ->whereKeyNot($claim->id)
                ->where('status', ClaimStatus::Pending)
                ->update([
                    'status' => ClaimStatus::Rejected->value,
                    'reviewed_by' => $moderator?->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => __('La fiche a été attribuée à un autre demandeur.'),
                ]);

            activity('owners')->performedOn($business)->causedBy($moderator ?? $claim->user)
                ->withProperties(['claim_id' => $claim->id, 'method' => $claim->method->value])
                ->log('claim.approved');
        });

        $claim->user->notify(new ClaimDecided($claim->fresh() ?? $claim, approved: true));
    }
}
