<?php

namespace App\Policies;

use App\Enums\Permission;

class PharmacyDutyPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ManagePharmacyDuties;
    }
}
