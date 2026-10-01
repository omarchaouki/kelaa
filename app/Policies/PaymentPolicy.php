<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Money is for admins only: moderators never hold this permission.
 */
class PaymentPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManagePayments;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->allowed($user);
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
