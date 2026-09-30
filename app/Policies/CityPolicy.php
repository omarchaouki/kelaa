<?php

namespace App\Policies;

use App\Enums\Permission;

class CityPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }
}
