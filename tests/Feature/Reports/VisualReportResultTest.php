<?php

namespace Tests\Feature\Reports;

use App\Services\Reports\Visual\VisualReportResultBuilder;
use Tests\TestCase;

class VisualReportResultTest extends TestCase
{
    public function test_result_builder_creates_totals_summary_and_chart_from_existing_rows(): void
    {
        $definition = [
            'datasource'=>'TEST',
            'columns'=>[
                ['field'=>'business_unit','label'=>'Business Unit'],
                ['field'=>'net_sales','label'=>'Net Sales','aggregate'=>'SUM','type'=>'money'],
            ],
            'summary'=>[['field'=>'net_sales','label'=>'Sales']],
            'chart'=>['type'=>'bar','label_field'=>'business_unit','value_field'=>'net_sales'],
        ];

        $result = app(VisualReportResultBuilder::class)->build('Sales by BU', $definition, [
            ['business_unit'=>'MEDAN','net_sales'=>100],
            ['business_unit'=>'JAKARTA','net_sales'=>50],
        ]);

        $this->assertSame(150.0, (float)$result->totals['net_sales']);
        $this->assertSame(150.0, (float)$result->summary['Sales']);
        $this->assertSame(['MEDAN','JAKARTA'], $result->metadata['chart']['labels']);
        $this->assertSame([100.0,50.0], $result->metadata['chart']['data']);
    }
}
