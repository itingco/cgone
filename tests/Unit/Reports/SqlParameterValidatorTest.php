<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Sql\SqlParameterValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SqlParameterValidatorTest extends TestCase
{
    public function test_coerces_supported_types_and_masks_sensitive_values(): void
    {
        $schema = [
            ['name'=>'start_date','type'=>'date','required'=>true],
            ['name'=>'limit_no','type'=>'integer','required'=>false],
            ['name'=>'amount','type'=>'decimal','required'=>false],
            ['name'=>'active','type'=>'boolean','required'=>false],
            ['name'=>'secret','type'=>'string','required'=>false,'sensitive'=>true],
        ];

        $v = new SqlParameterValidator();
        $values = $v->validate($schema, [
            'start_date'=>'2026-08-31','limit_no'=>'12','amount'=>'12.50','active'=>'1','secret'=>'abc',
        ]);

        $this->assertSame('2026-08-31',$values['start_date']);
        $this->assertSame(12,$values['limit_no']);
        $this->assertSame(12.5,$values['amount']);
        $this->assertTrue($values['active']);
        $this->assertSame('***',$v->mask($schema,$values)['secret']);
    }

    public function test_rejects_bad_parameter_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SqlParameterValidator())->definitions([
            ['name'=>'bad-name','type'=>'string'],
        ]);
    }
}
