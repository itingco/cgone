<?php

namespace Database\Seeders;

use App\Models\{Menu, Permission, Role, RoleMenuPermission};
use App\Models\Reports\{ReportDefinition, ReportRoleAccess};
use Illuminate\Database\Seeder;

class ReportingSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [];
        foreach (['view','create','edit','delete','export','print','share','clone','manage','execute'] as $code) {
            $permissions[$code] = Permission::firstOrCreate(
                ['code' => $code],
                ['name' => ucwords(str_replace('_', ' ', $code))]
            );
        }

        $section = Menu::updateOrCreate(
            ['code' => 'section.reports'],
            [
                'parent_id' => null,
                'label' => 'Reports',
                'route_name' => null,
                'sort_order' => 550,
                'is_active' => true,
            ]
        );

        $menus = [
            'reports.center' => ['Report Center', 'reports.center', 551],
            'reports.builder' => ['Report Builder', 'reports.builder.index', 552],
            'reports.sql' => ['Advanced SQL Reports', 'reports.sql.index', 553],
            'reports.admin' => ['Report Administration', 'reports.admin.performance', 554],
        ];

        foreach ($menus as $code => [$label, $routeName, $sort]) {
            Menu::updateOrCreate(
                ['code' => $code],
                [
                    'parent_id' => $section->id,
                    'label' => $label,
                    'route_name' => $routeName,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );
        }

        // Grant Report Center VIEW to every active role. The center itself contains
        // no unrestricted data; each report is still filtered by ReportAccessService.
        // This keeps report discovery separate from report-level authorization.
        $reportCenter = Menu::where('code', 'reports.center')->firstOrFail();
        foreach (Role::where('is_active', true)->get() as $role) {
            RoleMenuPermission::firstOrCreate([
                'role_id' => $role->id,
                'menu_id' => $reportCenter->id,
                'permission_id' => $permissions['view']->id,
            ]);
        }

        $admin = Role::where('code', 'ADMINISTRATOR')->first();
        if ($admin) {
            foreach (array_merge([$section], Menu::whereIn('code', array_keys($menus))->get()->all()) as $menu) {
                foreach ($permissions as $permission) {
                    RoleMenuPermission::firstOrCreate([
                        'role_id' => $admin->id,
                        'menu_id' => $menu->id,
                        'permission_id' => $permission->id,
                    ]);
                }
            }
        }

        $definitions = [
            ['SALES_HISTORY', 'Sales History', 'Sales', 'sales.history'],
            ['SALES_SUMMARY', 'Sales Summary', 'Sales', 'sales.history'],
            ['SALES_DETAIL', 'Sales Detail', 'Sales', 'sales.history'],
            ['SALES_BY_CUSTOMER', 'Sales by Customer', 'Sales', 'sales.history'],
            ['SALES_BY_ITEM', 'Sales by Item', 'Sales', 'sales.history'],
            ['SALES_BY_SALESPERSON', 'Sales by Salesperson', 'Sales', 'sales.history'],
            ['SALES_BY_LOCATION_BU', 'Sales by Location / Business Unit', 'Sales', 'sales.history'],
            ['SALES_OUTSTANDING_ORDERS', 'Outstanding Sales Orders', 'Sales', 'sales.outstanding-orders'],
            ['SALES_OUTSTANDING_SHIPMENTS', 'Shipment Not Invoiced', 'Sales', 'sales.outstanding-shipments'],
            ['CUSTOMER_OUTSTANDING', 'Customer Outstanding', 'Sales', 'sales.customer-aging'],
            ['CUSTOMER_STATEMENT', 'Customer Statement', 'Sales', 'sales.customer-aging'],
            ['CUSTOMER_AGING', 'Customer Aging', 'Sales', 'sales.customer-aging'],

            ['PURCHASE_HISTORY', 'Purchase History', 'Purchase', 'purchase.history'],
            ['PURCHASE_SUMMARY', 'Purchase Summary', 'Purchase', 'purchase.history'],
            ['PURCHASE_DETAIL', 'Purchase Detail', 'Purchase', 'purchase.history'],
            ['PURCHASE_BY_SUPPLIER', 'Purchase by Supplier', 'Purchase', 'purchase.history'],
            ['PURCHASE_BY_ITEM', 'Purchase by Item', 'Purchase', 'purchase.history'],
            ['PURCHASE_BY_LOCATION_BU', 'Purchase by Location / Business Unit', 'Purchase', 'purchase.history'],
            ['PURCHASE_OUTSTANDING_ORDERS', 'Outstanding Purchase Orders', 'Purchase', 'purchase.outstanding-orders'],
            ['PURCHASE_OUTSTANDING_RECEIPTS', 'Receipt Not Invoiced', 'Purchase', 'purchase.outstanding-receipts'],
            ['VENDOR_OUTSTANDING', 'Vendor Outstanding', 'Purchase', 'purchase.vendor-aging'],
            ['VENDOR_STATEMENT', 'Vendor Statement', 'Purchase', 'purchase.vendor-aging'],
            ['VENDOR_AGING', 'Vendor Aging', 'Purchase', 'purchase.vendor-aging'],

            ['STOCK_AVAILABILITY', 'Stock Availability', 'Inventory', 'inventory.stock-availability'],
            ['STOCK_MOVEMENT', 'Stock Movement', 'Inventory', 'inventory.stock-movement'],
            ['STOCK_CARD', 'Stock Card', 'Inventory', 'inventory.stock-movement'],
            ['STOCK_VALUATION', 'Stock Valuation', 'Inventory', 'inventory.stock-valuation'],
            ['NEGATIVE_STOCK', 'Negative Stock', 'Inventory', 'inventory.stock-availability'],
            ['REORDER_REPORT', 'Reorder Report', 'Inventory', 'inventory.stock-availability'],
            ['SLOW_MOVING', 'Slow Moving Stock', 'Inventory', 'inventory.stock-movement'],
            ['DEAD_STOCK', 'Dead Stock', 'Inventory', 'inventory.stock-movement'],
            ['FAST_MOVING', 'Fast Moving Stock', 'Inventory', 'inventory.stock-movement'],
            ['TRANSFER_HISTORY', 'Transfer History', 'Inventory', 'inventory.transfers'],
            ['COGS_DETAIL', 'COGS Detail', 'Inventory', 'inventory.stock-valuation'],
            ['INVENTORY_AGING', 'Inventory Aging', 'Inventory', 'inventory.stock-valuation'],

            ['GENERAL_LEDGER_DETAIL', 'General Ledger Detail', 'Finance', 'ledger.gl'],
            ['ACCOUNT_MOVEMENT', 'Account Movement', 'Finance', 'ledger.gl'],
            ['JOURNAL_REGISTER', 'Journal Register', 'Finance', 'finance.journal'],
            ['TRIAL_BALANCE', 'Trial Balance', 'Finance', 'finance.trial-balance'],
            ['BALANCE_SHEET', 'Balance Sheet', 'Finance', 'finance.balance-sheet'],
            ['PROFIT_LOSS', 'Profit & Loss', 'Finance', 'finance.profit-loss'],
            ['PROFIT_LOSS_BY_BU', 'Profit & Loss by Business Unit', 'Finance', 'finance.profit-loss'],
            ['CASH_FLOW', 'Cash Flow', 'Finance', 'finance.profit-loss'],
            ['AR_GL_RECONCILIATION', 'AR vs GL Reconciliation', 'Finance', 'finance.trial-balance'],
            ['AP_GL_RECONCILIATION', 'AP vs GL Reconciliation', 'Finance', 'finance.trial-balance'],
            ['INVENTORY_GL_RECONCILIATION', 'Inventory vs GL Reconciliation', 'Finance', 'finance.trial-balance'],

            ['EXECUTIVE_SALES_DASHBOARD', 'Executive Sales Dashboard', 'Management', 'sales.history'],
            ['EXECUTIVE_PURCHASE_DASHBOARD', 'Executive Purchase Dashboard', 'Management', 'purchase.history'],
            ['INVENTORY_HEALTH', 'Inventory Health', 'Management', 'inventory.stock-valuation'],
            ['WORKING_CAPITAL', 'Working Capital', 'Management', 'finance.trial-balance'],
            ['MONTHLY_PERFORMANCE', 'Monthly Performance', 'Management', 'finance.profit-loss'],
            ['BUSINESS_UNIT_PERFORMANCE', 'Business Unit Performance', 'Management', 'finance.profit-loss'],
        ];

        foreach ($definitions as [$code, $name, $category, $legacyMenu]) {
            $report = ReportDefinition::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'category' => $category,
                    'report_type' => ReportDefinition::TYPE_STANDARD,
                    'visibility' => ReportDefinition::VISIBILITY_SHARED,
                    'owner_id' => null,
                    'definition_json' => [
                        'standard_code' => $code,
                        'legacy_menu_code' => $legacyMenu,
                    ],
                    'is_system' => true,
                    'is_active' => true,
                ]
            );

            $this->migrateLegacyRoleAccess($report, $legacyMenu, $permissions);
        }
    }

    private function migrateLegacyRoleAccess(
        ReportDefinition $report,
        string $legacyMenuCode,
        array $permissions
    ): void {
        $legacyMenu = Menu::where('code', $legacyMenuCode)->first();
        $reportCenter = Menu::where('code', 'reports.center')->first();

        if (! $legacyMenu || ! $reportCenter || ! isset($permissions['view'])) {
            return;
        }

        $permissionIds = Permission::whereIn('code', ['view', 'export', 'print'])
            ->pluck('id', 'code');

        $roleIds = RoleMenuPermission::where('menu_id', $legacyMenu->id)
            ->whereIn('permission_id', $permissionIds->values())
            ->distinct()
            ->pluck('role_id');

        foreach ($roleIds as $roleId) {
            $granted = RoleMenuPermission::where('role_id', $roleId)
                ->where('menu_id', $legacyMenu->id)
                ->whereIn('permission_id', $permissionIds->values())
                ->pluck('permission_id')
                ->all();

            $canView = in_array($permissionIds['view'] ?? 0, $granted, true);
            if (! $canView) {
                continue;
            }

            ReportRoleAccess::updateOrCreate(
                [
                    'report_definition_id' => $report->id,
                    'role_id' => $roleId,
                ],
                [
                    'can_view' => true,
                    'can_export' => in_array($permissionIds['export'] ?? 0, $granted, true),
                    'can_print' => in_array($permissionIds['print'] ?? 0, $granted, true),
                    'can_edit' => false,
                    'can_share' => false,
                    'can_clone' => false,
                    'can_delete' => false,
                    'can_manage' => false,
                ]
            );

            RoleMenuPermission::firstOrCreate([
                'role_id' => $roleId,
                'menu_id' => $reportCenter->id,
                'permission_id' => $permissions['view']->id,
            ]);
        }
    }
}
