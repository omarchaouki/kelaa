<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

class BusinessClaimPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ModerateClaims;
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
