<?php

namespace App\Policies;

use App\Enums\Permission;

class CategoryPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }
}
