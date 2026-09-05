<?php

namespace Tests\Feature\Reports;

use App\Models\{Menu, Permission, Role, RoleMenuPermission, User};
use App\Models\Reports\ReportDefinition;
use Database\Seeders\{ReportingSeeder, ReportDatasourceSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualReportBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_private_visual_report(): void
    {
        $this->seed(ReportingSeeder::class);
        $this->seed(ReportDatasourceSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create(['code'=>'REPORT_DESIGNER','name'=>'Report Designer','is_active'=>true]);
        $user->roles()->attach($role->id);

        $menu = Menu::where('code','reports.builder')->firstOrFail();
        foreach (['view','create'] as $code) {
            $permission = Permission::where('code',$code)->firstOrFail();
            RoleMenuPermission::firstOrCreate([
                'role_id'=>$role->id,'menu_id'=>$menu->id,'permission_id'=>$permission->id,
            ]);
        }

        $response = $this->actingAs($user)->post(route('reports.builder.store'), [
            'name' => 'Sales by BU',
            'category' => 'Custom',
            'datasource' => 'SALES_INVOICE_DETAIL',
            'definition_json' => json_encode([
                'datasource' => 'SALES_INVOICE_DETAIL',
                'columns' => [
                    ['field'=>'business_unit'],
                    ['field'=>'net_sales','aggregate'=>'SUM'],
                ],
                'groups' => ['business_unit'],
            ]),
        ]);

        $report = ReportDefinition::where('name','Sales by BU')->firstOrFail();
        $response->assertRedirect(route('reports.run',$report));
        $this->assertSame(ReportDefinition::TYPE_VISUAL, $report->report_type);
        $this->assertSame(ReportDefinition::VISIBILITY_PRIVATE, $report->visibility);
        $this->assertSame($user->id, $report->owner_id);
    }
}
