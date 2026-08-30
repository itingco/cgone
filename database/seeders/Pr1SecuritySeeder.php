<?php
namespace Database\Seeders;

use App\Models\{Menu,Permission,Role,RoleMenuPermission};
use Illuminate\Database\Seeder;

class Pr1SecuritySeeder extends Seeder
{
    public function run(): void
    {
        $parent = Menu::updateOrCreate(
            ['code'=>'section.config'],
            ['label'=>'Configuration','sort_order'=>600,'is_active'=>true]
        );

        $menu = Menu::updateOrCreate(
            ['code'=>'config.departments'],
            ['parent_id'=>$parent->id,'label'=>'Departments','route_name'=>'config.departments.index','sort_order'=>62,'is_active'=>true]
        );

        $adminRole = Role::where('code','ADMINISTRATOR')->first();
        if (! $adminRole) return;

        Permission::where('code','<>','reverse')->get()->each(function ($permission) use ($adminRole, $menu) {
            RoleMenuPermission::firstOrCreate([
                'role_id'=>$adminRole->id,
                'menu_id'=>$menu->id,
                'permission_id'=>$permission->id,
            ]);
        });
    }
}
