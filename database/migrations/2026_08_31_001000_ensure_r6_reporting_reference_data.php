<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('menus')||!Schema::hasTable('permissions')||!Schema::hasTable('roles')||!Schema::hasTable('role_menu_permissions')||!Schema::hasTable('report_definitions')||!Schema::hasTable('report_role_access')) return;

        DB::transaction(function(): void {
            $now=now();
            $sectionId=DB::table('menus')->where('code','section.reports')->value('id');
            if(!$sectionId){
                $sectionId=DB::table('menus')->insertGetId(['parent_id'=>null,'code'=>'section.reports','label'=>'Reports','route_name'=>null,'icon'=>null,'sort_order'=>550,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now]);
            }
            DB::table('menus')->updateOrInsert(['code'=>'reports.admin'],[
                'parent_id'=>$sectionId,'label'=>'Report Administration','route_name'=>'reports.admin.performance','icon'=>null,'sort_order'=>554,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now,
            ]);

            $permissionIds=[];
            foreach(['view','create','edit','delete','export','print','share','clone','manage','execute'] as $code){
                DB::table('permissions')->updateOrInsert(['code'=>$code],['name'=>ucwords(str_replace('_',' ',$code)),'created_at'=>$now,'updated_at'=>$now]);
                $permissionIds[$code]=(int)DB::table('permissions')->where('code',$code)->value('id');
            }
            $adminId=DB::table('roles')->where('code','ADMINISTRATOR')->where('is_active',true)->value('id');
            $adminMenuId=DB::table('menus')->where('code','reports.admin')->value('id');
            if($adminId&&$adminMenuId){foreach($permissionIds as $permissionId)DB::table('role_menu_permissions')->updateOrInsert(['role_id'=>$adminId,'menu_id'=>$adminMenuId,'permission_id'=>$permissionId],[]);}

            $reports=[
                ['EXECUTIVE_SALES_DASHBOARD','Executive Sales Dashboard','Management','sales.history'],
                ['EXECUTIVE_PURCHASE_DASHBOARD','Executive Purchase Dashboard','Management','purchase.history'],
                ['INVENTORY_HEALTH','Inventory Health','Management','inventory.stock-valuation'],
                ['WORKING_CAPITAL','Working Capital','Management','finance.trial-balance'],
                ['MONTHLY_PERFORMANCE','Monthly Performance','Management','finance.profit-loss'],
                ['BUSINESS_UNIT_PERFORMANCE','Business Unit Performance','Management','finance.profit-loss'],
            ];
            $viewExportPrint=collect($permissionIds)->only(['view','export','print']);
            foreach($reports as [$code,$name,$category,$legacy]){
                $reportId=$this->ensureReport($code,$name,$category,$legacy);
                $this->copyLegacyAccess($reportId,$legacy,$viewExportPrint);
                if($adminId)$this->upsertAccess($reportId,(int)$adminId,[
                    'can_view'=>true,'can_export'=>true,'can_print'=>true,'can_edit'=>false,'can_share'=>true,'can_clone'=>true,'can_delete'=>false,'can_manage'=>true,
                ]);
            }
        });
    }

    public function down(): void {}

    private function ensureReport(string $code,string $name,string $category,string $legacy): int
    {
        $now=now();$values=['name'=>$name,'category'=>$category,'report_type'=>'STANDARD','visibility'=>'SHARED','owner_id'=>null,'definition_json'=>json_encode(['standard_code'=>$code,'legacy_menu_code'=>$legacy],JSON_UNESCAPED_SLASHES),'is_system'=>true,'is_active'=>true,'updated_at'=>$now];
        $id=DB::table('report_definitions')->where('code',$code)->value('id');
        if($id){DB::table('report_definitions')->where('id',$id)->update($values);return(int)$id;}
        return(int)DB::table('report_definitions')->insertGetId($values+['code'=>$code,'created_by'=>null,'updated_by'=>null,'created_at'=>$now]);
    }

    private function copyLegacyAccess(int $reportId,string $legacy,$permissionIds): void
    {
        if(!$permissionIds->has('view'))return;$menuId=DB::table('menus')->where('code',$legacy)->value('id');if(!$menuId)return;
        $roleIds=DB::table('role_menu_permissions')->where('menu_id',$menuId)->where('permission_id',$permissionIds['view'])->pluck('role_id');
        foreach($roleIds as $roleId){$granted=DB::table('role_menu_permissions')->where('role_id',$roleId)->where('menu_id',$menuId)->pluck('permission_id')->map(fn($v)=>(int)$v)->all();$this->upsertAccess($reportId,(int)$roleId,[
            'can_view'=>true,'can_export'=>$permissionIds->has('export')&&in_array((int)$permissionIds['export'],$granted,true),'can_print'=>$permissionIds->has('print')&&in_array((int)$permissionIds['print'],$granted,true),'can_edit'=>false,'can_share'=>false,'can_clone'=>false,'can_delete'=>false,'can_manage'=>false,
        ]);}
    }
    private function upsertAccess(int $reportId,int $roleId,array $values): void
    {
        DB::table('report_role_access')->updateOrInsert(['report_definition_id'=>$reportId,'role_id'=>$roleId],$values+['created_at'=>now(),'updated_at'=>now()]);
    }
};
