<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\Datasources\ReportDatasourceRegistry;
use App\Services\Reports\Visual\{VisualReportCompiler, VisualReportValidator};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisualReportCompilerTest extends TestCase
{
    use RefreshDatabase;

    public function test_compiler_builds_grouped_query_without_implicit_business_unit_filter(): void
    {
        $source = app(ReportDatasourceRegistry::class)->resolve('SALES_INVOICE_DETAIL');
        $definition = app(VisualReportValidator::class)->validate($source, [
            'columns' => [
                ['field' => 'business_unit'],
                ['field' => 'net_sales', 'aggregate' => 'SUM'],
            ],
            'groups' => ['business_unit'],
            'filters' => [],
            'sort' => [['field' => 'net_sales', 'direction' => 'desc']],
        ]);

        $query = app(VisualReportCompiler::class)->compile($source, $definition, preview: true);
        $sql = strtolower($query->toSql());

        $this->assertStringContainsString('group by', $sql);
        $this->assertStringNotContainsString('session', $sql);
        $this->assertStringNotContainsString('erp_business_unit', $sql);
    }

    public function test_preview_query_is_limited_to_500_rows(): void
    {
        $source = app(ReportDatasourceRegistry::class)->resolve('ITEM_MASTER');
        $definition = app(VisualReportValidator::class)->validate($source, [
            'columns' => [['field' => 'item_code'], ['field' => 'item_name']],
        ]);

        $query = app(VisualReportCompiler::class)->compile($source, $definition, preview: true);

        $this->assertSame(500, $query->limit);
    }
}
