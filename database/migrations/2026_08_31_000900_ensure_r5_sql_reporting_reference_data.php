<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('menus')||!Schema::hasTable('permissions')||!Schema::hasTable('roles')||!Schema::hasTable('role_menu_permissions')) return;

        $now=now();
        $sectionId=DB::table('menus')->where('code','section.reports')->value('id');
        if(!$sectionId){
            $sectionId=DB::table('menus')->insertGetId([
                'parent_id'=>null,'code'=>'section.reports','label'=>'Reports','route_name'=>null,'icon'=>null,
                'sort_order'=>550,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        DB::table('menus')->updateOrInsert(['code'=>'reports.sql'],[
            'parent_id'=>$sectionId,'label'=>'Advanced SQL Reports','route_name'=>'reports.sql.index','icon'=>null,
            'sort_order'=>553,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now,
        ]);
        $menuId=DB::table('menus')->where('code','reports.sql')->value('id');

        $permissionIds=[];
        foreach(['view','create','edit','delete','export','print','share','clone','manage','execute'] as $code){
            DB::table('permissions')->updateOrInsert(['code'=>$code],['name'=>ucwords(str_replace('_',' ',$code)),'created_at'=>$now,'updated_at'=>$now]);
            $permissionIds[]=DB::table('permissions')->where('code',$code)->value('id');
        }

        $adminId=DB::table('roles')->where('code','ADMINISTRATOR')->where('is_active',true)->value('id');
        if($adminId && $menuId){
            foreach($permissionIds as $permissionId){
                DB::table('role_menu_permissions')->updateOrInsert([
                    'role_id'=>$adminId,'menu_id'=>$menuId,'permission_id'=>$permissionId,
                ],[]);
            }
        }
    }

    public function down(): void {}
};
