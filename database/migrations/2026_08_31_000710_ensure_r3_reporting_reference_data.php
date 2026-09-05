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
            $permissionIds = DB::table('permissions')->whereIn('code',['view','export','print'])->pluck('id','code');

            $reports = [
                ['SALES_HISTORY','Sales History','Sales','sales.history'],
                ['SALES_SUMMARY','Sales Summary','Sales','sales.history'],
                ['SALES_DETAIL','Sales Detail','Sales','sales.history'],
                ['SALES_BY_CUSTOMER','Sales by Customer','Sales','sales.history'],
                ['SALES_BY_ITEM','Sales by Item','Sales','sales.history'],
                ['SALES_BY_SALESPERSON','Sales by Salesperson','Sales','sales.history'],
                ['SALES_BY_LOCATION_BU','Sales by Location / Business Unit','Sales','sales.history'],
                ['SALES_OUTSTANDING_ORDERS','Outstanding Sales Orders','Sales','sales.outstanding-orders'],
                ['SALES_OUTSTANDING_SHIPMENTS','Shipment Not Invoiced','Sales','sales.outstanding-shipments'],
                ['CUSTOMER_OUTSTANDING','Customer Outstanding','Sales','sales.customer-aging'],
                ['CUSTOMER_STATEMENT','Customer Statement','Sales','sales.customer-aging'],
                ['CUSTOMER_AGING','Customer Aging','Sales','sales.customer-aging'],

                ['PURCHASE_HISTORY','Purchase History','Purchase','purchase.history'],
                ['PURCHASE_SUMMARY','Purchase Summary','Purchase','purchase.history'],
                ['PURCHASE_DETAIL','Purchase Detail','Purchase','purchase.history'],
                ['PURCHASE_BY_SUPPLIER','Purchase by Supplier','Purchase','purchase.history'],
                ['PURCHASE_BY_ITEM','Purchase by Item','Purchase','purchase.history'],
                ['PURCHASE_BY_LOCATION_BU','Purchase by Location / Business Unit','Purchase','purchase.history'],
                ['PURCHASE_OUTSTANDING_ORDERS','Outstanding Purchase Orders','Purchase','purchase.outstanding-orders'],
                ['PURCHASE_OUTSTANDING_RECEIPTS','Receipt Not Invoiced','Purchase','purchase.outstanding-receipts'],
                ['VENDOR_OUTSTANDING','Vendor Outstanding','Purchase','purchase.vendor-aging'],
                ['VENDOR_STATEMENT','Vendor Statement','Purchase','purchase.vendor-aging'],
                ['VENDOR_AGING','Vendor Aging','Purchase','purchase.vendor-aging'],

                ['STOCK_AVAILABILITY','Stock Availability','Inventory','inventory.stock-availability'],
                ['STOCK_MOVEMENT','Stock Movement','Inventory','inventory.stock-movement'],
                ['STOCK_CARD','Stock Card','Inventory','inventory.stock-movement'],
                ['STOCK_VALUATION','Stock Valuation','Inventory','inventory.stock-valuation'],
                ['NEGATIVE_STOCK','Negative Stock','Inventory','inventory.stock-availability'],
                ['REORDER_REPORT','Reorder Report','Inventory','inventory.stock-availability'],
                ['SLOW_MOVING','Slow Moving Stock','Inventory','inventory.stock-movement'],
                ['DEAD_STOCK','Dead Stock','Inventory','inventory.stock-movement'],
                ['FAST_MOVING','Fast Moving Stock','Inventory','inventory.stock-movement'],
                ['TRANSFER_HISTORY','Transfer History','Inventory','inventory.transfers'],
                ['COGS_DETAIL','COGS Detail','Inventory','inventory.stock-valuation'],
                ['INVENTORY_AGING','Inventory Aging','Inventory','inventory.stock-valuation'],

                ['GENERAL_LEDGER_DETAIL','General Ledger Detail','Finance','ledger.gl'],
                ['ACCOUNT_MOVEMENT','Account Movement','Finance','ledger.gl'],
                ['JOURNAL_REGISTER','Journal Register','Finance','finance.journal'],
                ['TRIAL_BALANCE','Trial Balance','Finance','finance.trial-balance'],
                ['BALANCE_SHEET','Balance Sheet','Finance','finance.balance-sheet'],
                ['PROFIT_LOSS','Profit & Loss','Finance','finance.profit-loss'],
                ['PROFIT_LOSS_BY_BU','Profit & Loss by Business Unit','Finance','finance.profit-loss'],
                ['CASH_FLOW','Cash Flow','Finance','finance.profit-loss'],
                ['AR_GL_RECONCILIATION','AR vs GL Reconciliation','Finance','finance.trial-balance'],
                ['AP_GL_RECONCILIATION','AP vs GL Reconciliation','Finance','finance.trial-balance'],
                ['INVENTORY_GL_RECONCILIATION','Inventory vs GL Reconciliation','Finance','finance.trial-balance'],
            ];

            foreach ($reports as [$code,$name,$category,$legacyMenuCode]) {
                $reportId=$this->ensureReport($code,$name,$category,$legacyMenuCode);
                $this->copyLegacyAccess($reportId,$legacyMenuCode,$permissionIds);
            }

            $adminId=DB::table('roles')->where('code','ADMINISTRATOR')->value('id');
            if($adminId){
                foreach(DB::table('report_definitions')->where('is_system',true)->pluck('id') as $reportId){
                    $this->upsertAccess((int)$reportId,(int)$adminId,[
                        'can_view'=>true,'can_export'=>true,'can_print'=>true,
                        'can_edit'=>false,'can_share'=>true,'can_clone'=>true,
                        'can_delete'=>false,'can_manage'=>true,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Reference-data repair is deliberately non-destructive.
    }

    private function ensureReport(string $code,string $name,string $category,string $legacyMenuCode): int
    {
        $now=now();
        $values=[
            'name'=>$name,
            'category'=>$category,
            'report_type'=>'STANDARD',
            'visibility'=>'SHARED',
            'owner_id'=>null,
            'definition_json'=>json_encode([
                'standard_code'=>$code,
                'legacy_menu_code'=>$legacyMenuCode,
            ],JSON_UNESCAPED_SLASHES),
            'is_system'=>true,
            'is_active'=>true,
            'updated_at'=>$now,
        ];

        $id=DB::table('report_definitions')->where('code',$code)->value('id');
        if($id){
            DB::table('report_definitions')->where('id',$id)->update($values);
            return (int)$id;
        }

        return (int)DB::table('report_definitions')->insertGetId($values+[
            'code'=>$code,
            'created_by'=>null,
            'updated_by'=>null,
            'created_at'=>$now,
        ]);
    }

    private function copyLegacyAccess(int $reportId,string $legacyMenuCode,$permissionIds): void
    {
        if(!isset($permissionIds['view'])) return;

        $menuId=DB::table('menus')->where('code',$legacyMenuCode)->value('id');
        if(!$menuId) return;

        $roleIds=DB::table('role_menu_permissions')
            ->where('menu_id',$menuId)
            ->where('permission_id',$permissionIds['view'])
            ->pluck('role_id');

        foreach($roleIds as $roleId){
            $granted=DB::table('role_menu_permissions')
                ->where('role_id',$roleId)
                ->where('menu_id',$menuId)
                ->pluck('permission_id')
                ->map(fn($id)=>(int)$id)
                ->all();

            $this->upsertAccess($reportId,(int)$roleId,[
                'can_view'=>true,
                'can_export'=>isset($permissionIds['export']) && in_array((int)$permissionIds['export'],$granted,true),
                'can_print'=>isset($permissionIds['print']) && in_array((int)$permissionIds['print'],$granted,true),
                'can_edit'=>false,'can_share'=>false,'can_clone'=>false,'can_delete'=>false,'can_manage'=>false,
            ]);
        }
    }

    private function upsertAccess(int $reportId,int $roleId,array $values): void
    {
        $existing=DB::table('report_role_access')
            ->where('report_definition_id',$reportId)
            ->where('role_id',$roleId)
            ->value('id');

        if($existing){
            DB::table('report_role_access')->where('id',$existing)->update($values+['updated_at'=>now()]);
            return;
        }

        DB::table('report_role_access')->insert([
            'report_definition_id'=>$reportId,
            'role_id'=>$roleId,
        ]+$values+[
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
};
