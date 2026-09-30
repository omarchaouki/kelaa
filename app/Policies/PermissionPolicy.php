<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\User;

/**
 * CRUD policy where every ability requires a single permission.
 * Used by the admin panel resources.
 */
abstract class PermissionPolicy
{
    abstract protected function permission(): Permission;

    protected function allowed(User $user): bool
    {
        return ! $user->isBanned() && $user->can($this->permission()->value);
    }

    public function viewAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->allowed($user);
    }

    public function create(User $user): bool
    {
        return $this->allowed($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->allowed($user);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->allowed($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->allowed($user);
    }

    public function reorder(User $user): bool
    {
        return $this->allowed($user);
    }
}
