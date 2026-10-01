<?php

namespace App\Policies;

use App\Enums\Permission;

class PlacementRatePolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManagePlacements;
    }
}
