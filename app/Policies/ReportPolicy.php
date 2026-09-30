<?php

namespace App\Policies;

use App\Enums\Permission;

class ReportPolicy extends PermissionPolicy
{
    protected function permission(): Permission
    {
        return Permission::ModerateReports;
    }
}
