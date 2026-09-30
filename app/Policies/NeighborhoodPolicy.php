<?php

namespace App\Policies;

use App\Enums\Permission;

class NeighborhoodPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }
}
