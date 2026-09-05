<?php

namespace Tests\Feature\HumanCapital;

use Tests\TestCase;

final class H1SchemaTest extends TestCase
{
    public function test_h1_migration_declares_transaction_template_and_core_hr_schema(): void
    {
        $source = file_get_contents(database_path('migrations/2026_08_31_001100_create_transaction_templates_and_core_hr.php'));

        foreach ([
            'transaction_templates',
            'transaction_template_document_types',
            'employees',
            'employee_allocations',
            'departments',
            'sub_departments',
            'positions',
            'employee_levels',
            'employee_groups',
            'workgroups',
            'teams',
            'office_locations',
            'payroll_groups',
        ] as $table) {
            $this->assertStringContainsString("'{$table}'", $source);
        }

        foreach ([
            'transaction_template_id',
            'transaction_template_code_snapshot',
            'payment_term_days',
            'tax_posting_group_id',
            'number_series_code',
            'effective_from',
            'effective_to',
            'business_unit_id',
            'payroll_group_id',
            'report_to_employee_id',
        ] as $column) {
            $this->assertStringContainsString("'{$column}'", $source);
        }
    }

    public function test_h1_does_not_backfill_historical_business_unit_to_main(): void
    {
        $source = file_get_contents(database_path('migrations/2026_08_31_001100_create_transaction_templates_and_core_hr.php'));

        $this->assertStringNotContainsString("business_unit_id' => 1", $source);
        $this->assertStringNotContainsString("business_unit_id = 1", $source);
        $this->assertStringNotContainsString("'MAIN'", $source);
    }
}
