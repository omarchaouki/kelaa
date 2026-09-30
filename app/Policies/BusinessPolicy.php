<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

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
}
