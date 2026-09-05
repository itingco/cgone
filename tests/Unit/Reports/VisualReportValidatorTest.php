<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Services\Reports\Visual\VisualReportValidator;
use DomainException;
use Tests\TestCase;

class VisualReportValidatorTest extends TestCase
{
    private function validator(): array
    {
        $source = app(ReportDatasourceRegistry::class)->resolve('SALES_INVOICE_DETAIL');
        return [app(VisualReportValidator::class), $source];
    }

    public function test_validator_accepts_known_fields_and_four_group_levels(): void
    {
        [$validator, $source] = $this->validator();

        $validated = $validator->validate($source, [
            'datasource' => 'SALES_INVOICE_DETAIL',
            'columns' => [
                ['field' => 'business_unit'],
                ['field' => 'net_sales', 'aggregate' => 'SUM'],
            ],
            'groups' => ['business_unit'],
            'filters' => [
                ['field' => 'document_date', 'operator' => 'between', 'value' => ['2026-08-01','2026-08-31']],
            ],
            'sort' => [['field' => 'net_sales', 'direction' => 'desc']],
        ]);

        $this->assertSame('SALES_INVOICE_DETAIL', $validated['datasource']);
    }

    public function test_validator_rejects_unknown_field(): void
    {
        [$validator, $source] = $this->validator();
        $this->expectException(DomainException::class);

        $validator->validate($source, [
            'columns' => [['field' => 'drop_table']],
        ]);
    }

    public function test_validator_rejects_fifth_group_level(): void
    {
        [$validator, $source] = $this->validator();
        $this->expectException(DomainException::class);

        $validator->validate($source, [
            'columns' => [['field' => 'business_unit']],
            'groups' => ['business_unit','location','brand','category','customer_name'],
        ]);
    }

    public function test_validator_rejects_invalid_aggregate(): void
    {
        [$validator, $source] = $this->validator();
        $this->expectException(DomainException::class);

        $validator->validate($source, [
            'columns' => [['field' => 'net_sales', 'aggregate' => 'DROP']],
        ]);
    }
}
