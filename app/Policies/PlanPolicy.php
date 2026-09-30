<?php

namespace App\Policies;

use App\Enums\Permission;

class PlanPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManagePlans;
    }
}
