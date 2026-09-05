<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDefinition;
use Database\Seeders\{ReportingSeeder,SecuritySeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class R2ReportingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_r2_schema_and_report_definitions_are_available(): void
    {
        foreach (['sales_invoices','purchase_invoices','posted_sales_invoices','posted_purchase_invoices'] as $table) {
            $this->assertTrue(Schema::hasColumn($table,'due_date'), $table.' must have due_date');
        }

        $this->assertTrue(Schema::hasTable('customer_ledger_applications'));
        $this->assertTrue(Schema::hasTable('vendor_ledger_applications'));

        $this->seed(SecuritySeeder::class);
        $this->seed(ReportingSeeder::class);

        foreach ([
            'SALES_SUMMARY','SALES_DETAIL','SALES_BY_CUSTOMER','SALES_BY_ITEM','SALES_BY_SALESPERSON','SALES_BY_LOCATION_BU',
            'CUSTOMER_STATEMENT','CUSTOMER_AGING','PURCHASE_SUMMARY','PURCHASE_DETAIL','PURCHASE_BY_SUPPLIER','PURCHASE_BY_ITEM',
            'PURCHASE_BY_LOCATION_BU','VENDOR_STATEMENT','VENDOR_AGING',
        ] as $code) {
            $this->assertTrue(ReportDefinition::query()->where('code',$code)->where('is_active',true)->exists(), $code.' missing');
        }
    }
}
