<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\Sql\SqlSafetyValidator;
use InvalidArgumentException;
use Tests\TestCase;

class SqlReportProtectionTest extends TestCase
{
    public function test_write_sql_is_rejected_before_database_execution(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(SqlSafetyValidator::class)->assertSafe('DELETE FROM customers');
    }

    public function test_cte_select_is_allowed(): void
    {
        app(SqlSafetyValidator::class)->assertSafe('WITH sample AS (SELECT 1 AS n) SELECT n FROM sample');
        $this->assertTrue(true);
    }
}
