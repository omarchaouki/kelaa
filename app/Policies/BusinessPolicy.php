<?php

namespace App\Policies;

use App\Enums\BusinessStatus;
use App\Enums\Permission;
use App\Models\Business;
use App\Models\User;
use App\Services\Plans\PlanFeatures;

/**
 * Admin panel: moderators review, publish and suspend listings.
 * Listings are never hard-deleted from the panel (history, reviews, invoices).
 */
class BusinessPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ModerateBusinesses;
    }

    public function delete(User $user, mixed $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /**
     * Owner dashboard: only the verified owner of this very listing.
     * A suspended listing stays read-only for its owner.
     */
    public function manage(User $user, Business $business): bool
    {
        return ! $user->isBanned()
            && $business->owner_id === $user->id
            && $business->status !== BusinessStatus::Suspended;
    }

    /**
     * Statistics are a paid feature, and only for the owner.
     */
    public function viewStats(User $user, Business $business): bool
    {
        return $this->manage($user, $business) && app(PlanFeatures::class)->enabled($business, 'show_stats');
    }
}
