<?php

namespace Tests\Feature\Reports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_core_tables_and_record_business_unit_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('report_definitions', [
            'code', 'name', 'category', 'report_type', 'visibility', 'owner_id',
            'definition_json', 'is_system', 'is_active',
        ]));

        foreach ([
            'report_user_access',
            'report_role_access',
            'report_favorites',
            'report_saved_views',
            'report_execution_logs',
            'report_versions',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing reporting table {$table}");
        }

        foreach ([
            'sales_requests', 'sales_orders', 'shipments', 'sales_invoices',
            'purchase_requests', 'purchase_orders', 'receipts', 'purchase_invoices',
            'posted_shipments', 'posted_sales_invoices', 'posted_receipts', 'posted_purchase_invoices',
            'item_ledgers', 'customer_ledgers', 'vendor_ledgers',
        ] as $table) {
            $this->assertTrue(
                Schema::hasColumn($table, 'business_unit_id'),
                "{$table}.business_unit_id must exist because BU is stored on the record."
            );
        }
    }
}
