<?php
namespace Tests\Feature\HumanCapital;
use Tests\TestCase;
final class H2ReferenceDataTest extends TestCase
{
    public function test_human_capital_seeder_registers_h2_time_menus():void
    {
        $source=file_get_contents(database_path('seeders/HumanCapitalSeeder.php'));
        foreach(['hr.shifts','hr.schedules','hr.attendance','hr.attendance-corrections','hr.leave','hr.overtime','hr.holidays'] as $code)$this->assertStringContainsString($code,$source);
    }
    public function test_routes_expose_h2_time_management_flows():void
    {
        $source=file_get_contents(base_path('routes/human-capital.php'));
        foreach(['hr.shifts.index','hr.schedules.index','hr.attendance.index','hr.attendance-corrections.index','hr.leave.index','hr.overtime.index','hr.holidays.index'] as $route)$this->assertStringContainsString($route,$source);
    }
}
