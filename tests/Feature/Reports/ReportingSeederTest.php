<?php

namespace Tests\Feature\Reports;

use App\Models\{Menu, Permission, Role, RoleMenuPermission};
use App\Models\Reports\ReportDefinition;
use Database\Seeders\{ReportingSeeder, SecuritySeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_seeder_is_idempotent_and_registers_report_center(): void
    {
        $this->seed(SecuritySeeder::class);
        $this->seed(ReportingSeeder::class);
        $this->seed(ReportingSeeder::class);

        $this->assertDatabaseHas('menus', [
            'code' => 'reports.center',
            'route_name' => 'reports.center',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('report_definitions', [
            'code' => 'TRIAL_BALANCE',
            'report_type' => ReportDefinition::TYPE_STANDARD,
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->assertSame(46, ReportDefinition::query()->where('is_system', true)->count());

        foreach (['share', 'clone', 'manage', 'execute'] as $code) {
            $this->assertDatabaseHas('permissions', ['code' => $code]);
        }

        $admin = Role::where('code', 'ADMINISTRATOR')->firstOrFail();
        $center = Menu::where('code', 'reports.center')->firstOrFail();
        $manage = Permission::where('code', 'manage')->firstOrFail();

        $this->assertTrue(RoleMenuPermission::query()
            ->where('role_id', $admin->id)
            ->where('menu_id', $center->id)
            ->where('permission_id', $manage->id)
            ->exists());
    }


    public function test_reporting_seeder_grants_report_center_view_to_every_active_role(): void
    {
        $this->seed(SecuritySeeder::class);

        $role = Role::create([
            'code' => 'SALES_STAFF_TEST',
            'name' => 'Sales Staff Test',
            'is_active' => true,
        ]);

        $this->seed(ReportingSeeder::class);

        $center = Menu::where('code', 'reports.center')->firstOrFail();
        $view = Permission::where('code', 'view')->firstOrFail();

        $this->assertDatabaseHas('role_menu_permissions', [
            'role_id' => $role->id,
            'menu_id' => $center->id,
            'permission_id' => $view->id,
        ]);
    }
}
