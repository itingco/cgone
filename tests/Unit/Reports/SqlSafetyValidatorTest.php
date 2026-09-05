<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Sql\SqlSafetyValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SqlSafetyValidatorTest extends TestCase
{
    public function test_accepts_select_and_cte_queries(): void
    {
        $v = new SqlSafetyValidator();
        $v->assertSafe('SELECT id, name FROM customers WHERE id = :customer_id');
        $v->assertSafe('WITH x AS (SELECT 1 AS n) SELECT * FROM x;');
        $this->assertTrue(true);
    }

    /** @dataProvider unsafeSql */
    public function test_rejects_writes_and_multiple_statements(string $sql): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SqlSafetyValidator())->assertSafe($sql);
    }

    public static function unsafeSql(): array
    {
        return [
            ['UPDATE customers SET name = \'X\''],
            ['WITH x AS (DELETE FROM customers RETURNING id) SELECT * FROM x'],
            ['SELECT 1; DELETE FROM customers'],
            ['SELECT * FROM customers FOR UPDATE'],
            ['COPY customers TO \'/tmp/x\''],
        ];
    }

    public function test_semicolon_and_blocked_words_inside_strings_or_comments_are_ignored(): void
    {
        $v = new SqlSafetyValidator();
        $v->assertSafe("SELECT 'DELETE; DROP' AS note /* UPDATE x */ -- ALTER y\n");
        $this->assertTrue(true);
    }
}
