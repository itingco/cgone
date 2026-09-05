<?php
namespace Tests\Feature\HumanCapital;
use Tests\TestCase;
final class H2SchemaTest extends TestCase
{
    public function test_h2_migration_declares_time_management_schema():void
    {
        $source=file_get_contents(database_path('migrations/2026_09_01_000100_create_hr_time_management.php'));
        foreach(['shifts','shift_patterns','shift_pattern_days','employee_shift_assignments','work_schedule_overrides','attendance_records','attendance_corrections','holidays','leave_types','leave_balances','leave_requests','overtime_types','overtime_records'] as $table)$this->assertStringContainsString("'{$table}'",$source);
        foreach(['scheduled_break_minutes','scheduled_grace_late_minutes','scheduled_overtime_eligible','raw_check_in','raw_check_out','source_reference','requested_check_in','requested_check_out','balance_applied_at','balance_reversed_at','approved_hours','rate_percent'] as $column)$this->assertStringContainsString("'{$column}'",$source);
    }
    public function test_attendance_raw_values_are_distinct_from_correction_values():void
    {
        $source=file_get_contents(database_path('migrations/2026_09_01_000100_create_hr_time_management.php'));
        $this->assertStringContainsString("'raw_check_in'",$source);$this->assertStringContainsString("'requested_check_in'",$source);$this->assertStringContainsString("'attendance_corrections'",$source);
    }
}
