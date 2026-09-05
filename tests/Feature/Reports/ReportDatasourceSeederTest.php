<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\{ReportDatasource, ReportField};
use Database\Seeders\ReportDatasourceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportDatasourceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_visual_reporting_datasource_tables_and_seed_data_exist(): void
    {
        $this->assertTrue(Schema::hasTable('report_datasources'));
        $this->assertTrue(Schema::hasTable('report_fields'));

        $this->seed(ReportDatasourceSeeder::class);

        foreach ([
            'SALES_INVOICE_DETAIL','SALES_ORDER_DETAIL','CUSTOMER_LEDGER',
            'PURCHASE_INVOICE_DETAIL','PURCHASE_ORDER_DETAIL','VENDOR_LEDGER',
            'INVENTORY_MOVEMENT','STOCK_POSITION','GENERAL_LEDGER',
            'ITEM_MASTER','CUSTOMER_MASTER','SUPPLIER_MASTER',
            'CHART_OF_ACCOUNTS','AUDIT_ACTIVITY',
        ] as $code) {
            $this->assertDatabaseHas('report_datasources', ['code' => $code, 'is_active' => true]);
        }

        $sales = ReportDatasource::where('code', 'SALES_INVOICE_DETAIL')->firstOrFail();
        $this->assertTrue(
            ReportField::where('report_datasource_id', $sales->id)->where('key', 'business_unit')->exists()
        );
    }
}
