<?php

namespace Tests\Feature\HumanCapital;

use Tests\TestCase;

final class H1ReferenceDataTest extends TestCase
{
    public function test_human_capital_seeder_registers_core_h1_menus(): void
    {
        $source = file_get_contents(database_path('seeders/HumanCapitalSeeder.php'));

        foreach ([
            'section.human-capital',
            'hr.employees',
            'hr.allocations',
            'hr.departments',
            'hr.sub-departments',
            'hr.positions',
            'hr.levels',
            'hr.groups',
            'hr.workgroups',
            'hr.teams',
            'hr.office-locations',
            'hr.payroll-groups',
            'config.transaction-templates',
        ] as $code) {
            $this->assertStringContainsString($code, $source);
        }
    }

    public function test_database_seeder_runs_human_capital_seeder(): void
    {
        $source = file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        $this->assertStringContainsString('HumanCapitalSeeder::class', $source);
    }
}
