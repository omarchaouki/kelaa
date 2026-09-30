<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * Staff manage accounts (roles, bans) but never delete them from the panel:
 * deletion is the user's own right (Law 09-08) and anonymizes their reviews.
 */
class UserPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageUsers;
    }

    public function create(User $user): bool
    {
        return false;
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
