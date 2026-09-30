<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\ReviewReply;
use App\Models\User;

class ReviewReplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->moderates($user);
    }

    public function view(User $user, ReviewReply $reply): bool
    {
        return $this->moderates($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ReviewReply $reply): bool
    {
        return false;
    }

    public function moderate(User $user, ReviewReply $reply): bool
    {
        return $this->moderates($user);
    }

    public function delete(User $user, ReviewReply $reply): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    private function moderates(User $user): bool
    {
        return ! $user->isBanned() && $user->can(Permission::ModerateReviews->value);
    }
}
