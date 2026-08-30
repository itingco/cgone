<?php
namespace Tests\Feature\Configuration;

use App\Models\{Menu,Permission,Role,RoleDataScope,RoleMenuPermission,User};
use App\Services\Security\DataScopeService;
use Database\Seeders\SecuritySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_role_copies_permissions_and_data_scopes(): void
    {
        $this->seed(SecuritySeeder::class);
        $admin=User::where('email','admin@erp.local')->firstOrFail();
        $role=Role::create(['code'=>'SALES_ABC','name'=>'Sales ABC','is_active'=>true]);
        $menu=Menu::where('code','sales.invoice')->firstOrFail();
        $view=Permission::where('code','view')->firstOrFail();
        RoleMenuPermission::create(['role_id'=>$role->id,'menu_id'=>$menu->id,'permission_id'=>$view->id]);
        RoleDataScope::create(['role_id'=>$role->id,'menu_id'=>$menu->id,'field'=>'status','operator'=>'=','value'=>'OPEN','sort_order'=>0,'is_active'=>true]);

        $this->actingAs($admin)->post('/configuration/roles/'.$role->id.'/duplicate')->assertRedirect();
        $copy=Role::where('code','LIKE','COPY_SALES_ABC%')->firstOrFail();
        $this->assertSame('Copy Sales ABC',$copy->name);
        $this->assertDatabaseHas('role_menu_permissions',['role_id'=>$copy->id,'menu_id'=>$menu->id,'permission_id'=>$view->id]);
        $this->assertDatabaseHas('role_data_scopes',['role_id'=>$copy->id,'menu_id'=>$menu->id,'field'=>'status','operator'=>'=','value'=>'OPEN']);
    }

    public function test_data_scope_filters_rows_and_no_scope_means_full_access(): void
    {
        $menu=Menu::create(['code'=>'test.scope','label'=>'Test','sort_order'=>1,'is_active'=>true]);
        $view=Permission::create(['code'=>'view','name'=>'View']);
        $role=Role::create(['code'=>'TEST','name'=>'Test','is_active'=>true]);
        $actor=User::factory()->create(['is_active'=>true]);
        $active=User::factory()->create(['is_active'=>true]);
        User::factory()->create(['is_active'=>false]);
        $actor->roles()->attach($role);
        RoleMenuPermission::create(['role_id'=>$role->id,'menu_id'=>$menu->id,'permission_id'=>$view->id]);
        config()->set('role-data-scopes.test.scope',['is_active'=>['label'=>'Active','column'=>'is_active','operators'=>['=']]]);

        $service=app(DataScopeService::class);
        $this->assertGreaterThanOrEqual(3,$service->apply(User::query(),$actor,'test.scope')->count());

        RoleDataScope::create(['role_id'=>$role->id,'menu_id'=>$menu->id,'field'=>'is_active','operator'=>'=','value'=>'1','is_active'=>true]);
        $ids=$service->apply(User::query(),$actor,'test.scope')->pluck('id');
        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse(User::where('is_active',false)->whereIn('id',$ids)->exists());
    }
}
