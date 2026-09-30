<?php

namespace App\Policies;

use App\Enums\Permission;

class SearchSynonymPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManageCatalog;
    }
}
