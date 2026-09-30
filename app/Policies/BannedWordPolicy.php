<?php

namespace App\Policies;

use App\Enums\Permission;

class BannedWordPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }
}
