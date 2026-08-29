<?php

namespace App\Services\Security;

use App\Models\RoleMenuPermission;
use App\Models\User;

class MenuAuthorizationService
{
    /** @var array<int, \Illuminate\Support\Collection<int, int>> */
    private array $roleIdsByUser = [];

    /** @var array<string, bool> */
    private array $decisionCache = [];

    public function allows(User $user, string $menuCode, string $permissionCode): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $userId = (int) $user->getKey();
        $cacheKey = $userId.'|'.$menuCode.'|'.$permissionCode;

        if (array_key_exists($cacheKey, $this->decisionCache)) {
            return $this->decisionCache[$cacheKey];
        }

        $roleIds = $this->roleIdsByUser[$userId] ??= $user->roles()
            ->where('roles.is_active', true)
            ->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return $this->decisionCache[$cacheKey] = false;
        }

        return $this->decisionCache[$cacheKey] = RoleMenuPermission::query()
            ->whereIn('role_id', $roleIds)
            ->whereHas('menu', fn ($q) => $q
                ->where('code', $menuCode)
                ->where('is_active', true))
            ->whereHas('permission', fn ($q) => $q->where('code', $permissionCode))
            ->exists();
    }
}
