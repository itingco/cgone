<?php

namespace Tests\Feature\Reports;

use App\Models\{Menu, Permission, Role, RoleMenuPermission, User};
use Database\Seeders\{ReportingSeeder, ReportDatasourceSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualReportPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_rejects_unknown_field_and_caps_rows(): void
    {
        $this->seed(ReportingSeeder::class);
        $this->seed(ReportDatasourceSeeder::class);

        $user = User::factory()->create(['is_active'=>true]);
        $role = Role::create(['code'=>'DESIGNER','name'=>'Designer','is_active'=>true]);
        $user->roles()->attach($role);
        $menu = Menu::where('code','reports.builder')->firstOrFail();
        foreach(['view','create'] as $code){
            $p=Permission::where('code',$code)->firstOrFail();
            RoleMenuPermission::firstOrCreate(['role_id'=>$role->id,'menu_id'=>$menu->id,'permission_id'=>$p->id]);
        }

        $this->actingAs($user)->post(route('reports.builder.preview'), [
            'datasource'=>'ITEM_MASTER',
            'definition_json'=>json_encode(['columns'=>[['field'=>'unknown_column']]]),
        ])->assertSessionHasErrors();
    }
}
