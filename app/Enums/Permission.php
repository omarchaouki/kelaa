<?php

namespace App\Enums;

/**
 * Granular permissions checked by policies and the admin panel.
 * Roles map onto these in Permission::forRole().
 */
enum Permission: string
{
    case ModerateReviews = 'moderate reviews';
    case ModerateBusinesses = 'moderate businesses';
    case ModerateClaims = 'moderate claims';
    case ModerateReports = 'moderate reports';
    case ModerateMedia = 'moderate media';
    case ModerateEditSuggestions = 'moderate edit suggestions';
    case ManagePharmacyDuties = 'manage pharmacy duties';
    case ManageCatalog = 'manage catalog';
    case ManageUsers = 'manage users';
    case ManagePayments = 'manage payments';
    case ManagePlans = 'manage plans';
    case ManagePlacements = 'manage placements';
    case ViewActivityLog = 'view activity log';

    /**
     * @return list<self>
     */
    public static function forRole(Role $role): array
    {
        return match ($role) {
            Role::Admin => self::cases(),
            Role::Moderator => [
                self::ModerateReviews,
                self::ModerateBusinesses,
                self::ModerateClaims,
                self::ModerateReports,
                self::ModerateMedia,
                self::ModerateEditSuggestions,
                self::ManagePharmacyDuties,
            ],
            Role::BusinessOwner, Role::Member => [],
        };
    }
}
