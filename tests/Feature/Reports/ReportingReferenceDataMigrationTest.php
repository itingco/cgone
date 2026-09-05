<?php

namespace Tests\Feature\Reports;

use App\Models\{Menu, Permission, Role, RoleMenuPermission};
use App\Models\Reports\ReportDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingReferenceDataMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_reference_data_exists_after_migrations(): void
    {
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
    }

    public function test_administrator_can_view_report_center_after_migrations_and_seeding(): void
    {
        $this->seed();

        $admin = Role::where('code', 'ADMINISTRATOR')->firstOrFail();
        $center = Menu::where('code', 'reports.center')->firstOrFail();
        $view = Permission::where('code', 'view')->firstOrFail();

        $this->assertTrue(RoleMenuPermission::query()
            ->where('role_id', $admin->id)
            ->where('menu_id', $center->id)
            ->where('permission_id', $view->id)
            ->exists());
    }
}
