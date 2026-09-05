<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')
            || ! Schema::hasTable('permissions')
            || ! Schema::hasTable('roles')
            || ! Schema::hasTable('role_menu_permissions')
            || ! Schema::hasTable('report_definitions')
            || ! Schema::hasTable('report_role_access')) {
            return;
        }

        DB::transaction(function (): void {
            $permissionIds = [];
            foreach (['view','create','edit','delete','export','print','share','clone','manage','execute'] as $code) {
                $permissionIds[$code] = $this->ensureRow('permissions', ['code' => $code], [
                    'name' => ucwords(str_replace('_', ' ', $code)),
                ]);
            }

            $sectionId = $this->ensureRow('menus', ['code' => 'section.reports'], [
                'parent_id' => null,
                'label' => 'Reports',
                'route_name' => null,
                'icon' => null,
                'sort_order' => 550,
                'is_active' => true,
            ]);

            $menuDefinitions = [
                'reports.center' => ['Report Center', 'reports.center', 551],
                'reports.builder' => ['Report Builder', 'reports.builder.index', 552],
                'reports.sql' => ['Advanced SQL Reports', 'reports.center', 553],
                'reports.admin' => ['Report Administration', 'reports.center', 554],
            ];

            $menuIds = [];
            foreach ($menuDefinitions as $code => [$label, $routeName, $sortOrder]) {
                $menuIds[$code] = $this->ensureRow('menus', ['code' => $code], [
                    'parent_id' => $sectionId,
                    'label' => $label,
                    'route_name' => $routeName,
                    'icon' => null,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                ]);
            }

            // Report Center itself is discoverable by every active role. Report-level
            // access remains controlled separately by report_role_access/report_user_access.
            $activeRoleIds = DB::table('roles')->where('is_active', true)->pluck('id');
            foreach ($activeRoleIds as $roleId) {
                $this->ensurePivot('role_menu_permissions', [
                    'role_id' => (int) $roleId,
                    'menu_id' => $menuIds['reports.center'],
                    'permission_id' => $permissionIds['view'],
                ]);
            }

            // Administrator keeps full control of the Reports section and its tools.
            $adminId = DB::table('roles')->where('code', 'ADMINISTRATOR')->value('id');
            if ($adminId) {
                foreach (array_merge([$sectionId], array_values($menuIds)) as $menuId) {
                    foreach ($permissionIds as $permissionId) {
                        $this->ensurePivot('role_menu_permissions', [
                            'role_id' => (int) $adminId,
                            'menu_id' => (int) $menuId,
                            'permission_id' => (int) $permissionId,
                        ]);
                    }
                }
            }

            $standardReports = [
                ['SALES_HISTORY', 'Sales History', 'Sales', 'sales.history'],
                ['SALES_OUTSTANDING_ORDERS', 'Outstanding Sales Orders', 'Sales', 'sales.outstanding-orders'],
                ['SALES_OUTSTANDING_SHIPMENTS', 'Shipment Not Invoiced', 'Sales', 'sales.outstanding-shipments'],
                ['CUSTOMER_OUTSTANDING', 'Customer Outstanding', 'Sales', 'sales.customer-aging'],
                ['PURCHASE_HISTORY', 'Purchase History', 'Purchase', 'purchase.history'],
                ['PURCHASE_OUTSTANDING_ORDERS', 'Outstanding Purchase Orders', 'Purchase', 'purchase.outstanding-orders'],
                ['PURCHASE_OUTSTANDING_RECEIPTS', 'Receipt Not Invoiced', 'Purchase', 'purchase.outstanding-receipts'],
                ['VENDOR_OUTSTANDING', 'Vendor Outstanding', 'Purchase', 'purchase.vendor-aging'],
                ['STOCK_AVAILABILITY', 'Stock Availability', 'Inventory', 'inventory.stock-availability'],
                ['STOCK_MOVEMENT', 'Stock Movement', 'Inventory', 'inventory.stock-movement'],
                ['STOCK_VALUATION', 'Stock Valuation', 'Inventory', 'inventory.stock-valuation'],
                ['JOURNAL_REGISTER', 'Journal Register', 'Finance', 'finance.journal'],
                ['TRIAL_BALANCE', 'Trial Balance', 'Finance', 'finance.trial-balance'],
                ['BALANCE_SHEET', 'Balance Sheet', 'Finance', 'finance.balance-sheet'],
                ['PROFIT_LOSS', 'Profit & Loss', 'Finance', 'finance.profit-loss'],
            ];

            foreach ($standardReports as [$code, $name, $category, $legacyMenuCode]) {
                $reportId = $this->ensureRow('report_definitions', ['code' => $code], [
                    'name' => $name,
                    'category' => $category,
                    'report_type' => 'STANDARD',
                    'visibility' => 'SHARED',
                    'owner_id' => null,
                    'definition_json' => json_encode([
                        'standard_code' => $code,
                        'legacy_menu_code' => $legacyMenuCode,
                    ], JSON_UNESCAPED_SLASHES),
                    'is_system' => true,
                    'is_active' => true,
                    'created_by' => null,
                    'updated_by' => null,
                ]);

                $this->copyLegacyReportRoleAccess($reportId, $legacyMenuCode, $permissionIds);
            }
        });
    }

    public function down(): void
    {
        // Reference-data repair is intentionally non-destructive on rollback.
    }

    private function ensureRow(string $table, array $key, array $values): int
    {
        $query = DB::table($table);
        foreach ($key as $column => $value) {
            $query->where($column, $value);
        }

        $existingId = $query->value('id');
        $now = now();

        if ($existingId) {
            DB::table($table)->where('id', $existingId)->update($values + ['updated_at' => $now]);
            return (int) $existingId;
        }

        return (int) DB::table($table)->insertGetId($key + $values + [
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensurePivot(string $table, array $key): void
    {
        $query = DB::table($table);
        foreach ($key as $column => $value) {
            $query->where($column, $value);
        }

        if (! $query->exists()) {
            DB::table($table)->insert($key);
        }
    }

    private function copyLegacyReportRoleAccess(int $reportId, string $legacyMenuCode, array $permissionIds): void
    {
        $legacyMenuId = DB::table('menus')->where('code', $legacyMenuCode)->value('id');
        if (! $legacyMenuId) {
            return;
        }

        $roleIds = DB::table('role_menu_permissions')
            ->where('menu_id', $legacyMenuId)
            ->where('permission_id', $permissionIds['view'])
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $grantedPermissionIds = DB::table('role_menu_permissions')
                ->where('role_id', $roleId)
                ->where('menu_id', $legacyMenuId)
                ->pluck('permission_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $access = [
                'can_view' => true,
                'can_export' => in_array($permissionIds['export'], $grantedPermissionIds, true),
                'can_print' => in_array($permissionIds['print'], $grantedPermissionIds, true),
                'can_edit' => false,
                'can_share' => false,
                'can_clone' => false,
                'can_delete' => false,
                'can_manage' => false,
            ];

            $existing = DB::table('report_role_access')
                ->where('report_definition_id', $reportId)
                ->where('role_id', $roleId)
                ->value('id');

            if ($existing) {
                DB::table('report_role_access')->where('id', $existing)->update($access + ['updated_at' => now()]);
            } else {
                DB::table('report_role_access')->insert([
                    'report_definition_id' => $reportId,
                    'role_id' => (int) $roleId,
                ] + $access + [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
