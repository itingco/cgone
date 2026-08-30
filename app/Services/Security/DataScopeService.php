<?php
namespace App\Services\Security;

use App\Models\{Menu,Permission,RoleDataScope,User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DataScopeService
{
    public function apply(Builder $query, User $user, string $menuCode, string $permissionCode = 'view'): Builder
    {
        $definitions = config('role-data-scopes.'.$menuCode, []);
        if (empty($definitions)) return $query;

        $menu = Menu::query()->where('code', $menuCode)->where('is_active', true)->first();
        $permission = Permission::query()->where('code', $permissionCode)->first();
        if (! $menu || ! $permission) return $query->whereRaw('1 = 0');

        $roleIds = $user->roles()
            ->where('roles.is_active', true)
            ->whereHas('grants', fn ($q) => $q
                ->where('menu_id', $menu->id)
                ->where('permission_id', $permission->id))
            ->pluck('roles.id')
            ->map(fn ($id) => (int) $id);

        if ($roleIds->isEmpty()) return $query->whereRaw('1 = 0');

        $rules = RoleDataScope::query()
            ->whereIn('role_id', $roleIds)
            ->where('menu_id', $menu->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('role_id');

        // A granting role without rules means unrestricted rows for this module.
        foreach ($roleIds as $roleId) {
            if (! $rules->has($roleId) || $rules->get($roleId)->isEmpty()) return $query;
        }

        return $query->where(function (Builder $outer) use ($roleIds, $rules, $definitions) {
            foreach ($roleIds as $roleId) {
                $outer->orWhere(function (Builder $roleQuery) use ($rules, $roleId, $definitions) {
                    $this->applyRoleRules($roleQuery, $rules->get($roleId, collect()), $definitions);
                });
            }
        });
    }

    private function applyRoleRules(Builder $query, Collection $rules, array $definitions): void
    {
        foreach ($rules as $rule) {
            $definition = $definitions[$rule->field] ?? null;
            if (! $definition || ! in_array($rule->operator, $definition['operators'] ?? [], true)) {
                // Stale/tampered rules fail closed instead of silently widening access.
                $query->whereRaw('1 = 0');
                continue;
            }

            $column = $definition['column'];
            $operator = strtoupper($rule->operator);
            $value = $rule->value;

            match ($operator) {
                '=' => $query->where($column, '=', $value),
                '!=' => $query->where($column, '<>', $value),
                'IN' => $this->applyIn($query, $column, $value, false),
                'NOT IN' => $this->applyIn($query, $column, $value, true),
                'IS NULL' => $query->whereNull($column),
                'IS NOT NULL' => $query->whereNotNull($column),
                default => $query->whereRaw('1 = 0'),
            };
        }
    }

    private function applyIn(Builder $query, string $column, ?string $raw, bool $not): void
    {
        $values = array_values(array_filter(array_map('trim', explode(',', (string) $raw)), fn ($v) => $v !== ''));
        if ($values === []) {
            $query->whereRaw('1 = 0');
            return;
        }
        $not ? $query->whereNotIn($column, $values) : $query->whereIn($column, $values);
    }
}
