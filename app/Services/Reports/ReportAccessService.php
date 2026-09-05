<?php

namespace App\Services\Reports;

use App\Models\Reports\{ReportDefinition, ReportRoleAccess, ReportUserAccess};
use App\Models\User;
use App\Services\Security\MenuAuthorizationService;

final class ReportAccessService
{
    private const PERMISSIONS = ['view','export','print','edit','share','clone','delete','manage'];

    public function __construct(private readonly MenuAuthorizationService $menuAuth)
    {
    }

    public function permissions(User $user, ReportDefinition $report): ReportPermissionSet
    {
        $grants = array_fill_keys(self::PERMISSIONS, false);

        if (! $user->is_active || ! $report->is_active) {
            return new ReportPermissionSet($grants);
        }

        if ($report->report_type === ReportDefinition::TYPE_SQL
            && ! $this->menuAuth->allows($user, 'reports.sql', 'view')) {
            return new ReportPermissionSet($grants);
        }

        if (! $report->is_system && (int) $report->owner_id === (int) $user->id) {
            foreach (self::PERMISSIONS as $permission) {
                $grants[$permission] = true;
            }
        }

        $legacyMenu = data_get($report->definition_json, 'legacy_menu_code');
        if ($report->is_system && $legacyMenu) {
            foreach (['view','export','print'] as $permission) {
                $grants[$permission] = $grants[$permission]
                    || $this->menuAuth->allows($user, (string) $legacyMenu, $permission);
            }
        } elseif ($report->visibility === ReportDefinition::VISIBILITY_COMPANY) {
            $grants['view'] = true;
        }

        $roleIds = $user->roles()
            ->where('roles.is_active', true)
            ->pluck('roles.id');

        if ($roleIds->isNotEmpty()) {
            $roleRows = ReportRoleAccess::query()
                ->where('report_definition_id', $report->id)
                ->whereIn('role_id', $roleIds)
                ->get();

            foreach ($roleRows as $row) {
                foreach (self::PERMISSIONS as $permission) {
                    $grants[$permission] = $grants[$permission]
                        || (bool) $row->{'can_'.$permission};
                }
            }
        }

        $userRow = ReportUserAccess::query()
            ->where('report_definition_id', $report->id)
            ->where('user_id', $user->id)
            ->first();

        if ($userRow) {
            foreach (self::PERMISSIONS as $permission) {
                $grants[$permission] = $grants[$permission]
                    || (bool) $userRow->{'can_'.$permission};
            }
        }

        if ($report->is_system) {
            $grants['delete'] = false;
        }

        if ($report->report_type === ReportDefinition::TYPE_SQL) {
            foreach (self::PERMISSIONS as $permission) {
                $grants[$permission] = $grants[$permission]
                    && $this->menuAuth->allows($user, 'reports.sql', $permission);
            }
        }

        return new ReportPermissionSet($grants);
    }

    public function allows(User $user, ReportDefinition $report, string $permission): bool
    {
        return $this->permissions($user, $report)->allows($permission);
    }
}
