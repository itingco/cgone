<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Sql\SqlParameterDefinition;
use PHPUnit\Framework\TestCase;

final class SqlSensitiveParameterSchemaTest extends TestCase
{
    public function test_sensitive_flag_is_preserved_in_runtime_schema(): void
    {
        $definition=SqlParameterDefinition::fromArray([
            'name'=>'secret_filter','type'=>'string','required'=>true,'sensitive'=>true,
        ]);

        $this->assertTrue($definition->reportSchema()['sensitive']);
    }
}
