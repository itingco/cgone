<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use InvalidArgumentException;
use Tests\TestCase;

class ReportDatasourceRegistryTest extends TestCase
{
    public function test_registry_resolves_allowlisted_datasource(): void
    {
        $source = app(ReportDatasourceRegistry::class)->resolve('sales_invoice_detail');

        $this->assertSame('SALES_INVOICE_DETAIL', $source->code());
        $this->assertArrayHasKey('business_unit', $source->fieldMap());
        $this->assertArrayHasKey('net_sales', $source->fieldMap());
    }

    public function test_registry_rejects_unknown_datasource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(ReportDatasourceRegistry::class)->resolve('RAW_TABLE_NAME');
    }
}
