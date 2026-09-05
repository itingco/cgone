<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDefinition;
use App\Services\Reports\Sql\SqlSafetyValidator;
use Tests\TestCase;

class R5SqlReportingSchemaTest extends TestCase
{
    public function test_sql_report_type_and_r5_services_exist(): void
    {
        $this->assertSame('SQL',ReportDefinition::TYPE_SQL);
        $this->assertTrue(class_exists(SqlSafetyValidator::class));
        $this->assertTrue(class_exists(\App\Services\Reports\Sql\SqlReportExecutor::class));
        $this->assertTrue(class_exists(\App\Reports\Sql\StoredSqlReport::class));
    }
}
