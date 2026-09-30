<?php

namespace App\Policies;

use App\Enums\Permission;

class PagePolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }
}
