<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(DB::getDriverName()!=='pgsql') return;
        $indexes=[
            'CREATE INDEX IF NOT EXISTS idx_gl_batches_bu_posting ON gl_batches (business_unit_id, posting_at)',
            'CREATE INDEX IF NOT EXISTS idx_posted_sales_invoices_date_bu ON posted_sales_invoices (document_date, business_unit_id)',
            'CREATE INDEX IF NOT EXISTS idx_posted_purchase_invoices_date_bu ON posted_purchase_invoices (document_date, business_unit_id)',
            'CREATE INDEX IF NOT EXISTS idx_customer_ledgers_entity_bu_posting ON customer_ledgers (customer_id, business_unit_id, posting_at)',
            'CREATE INDEX IF NOT EXISTS idx_vendor_ledgers_entity_bu_posting ON vendor_ledgers (vendor_id, business_unit_id, posting_at)',
            'CREATE INDEX IF NOT EXISTS idx_item_ledgers_item_bu_posting ON item_ledgers (item_id, business_unit_id, posting_at)',
            'CREATE INDEX IF NOT EXISTS idx_item_ledgers_bu_posting ON item_ledgers (business_unit_id, posting_at)',
            'CREATE INDEX IF NOT EXISTS idx_report_execution_status_started_report ON report_execution_logs (status, started_at, report_definition_id)',
        ];
        foreach($indexes as $sql) DB::statement($sql);
    }

    public function down(): void
    {
        if(DB::getDriverName()!=='pgsql') return;
        foreach([
            'idx_gl_batches_bu_posting','idx_posted_sales_invoices_date_bu','idx_posted_purchase_invoices_date_bu',
            'idx_customer_ledgers_entity_bu_posting','idx_vendor_ledgers_entity_bu_posting','idx_item_ledgers_item_bu_posting',
            'idx_item_ledgers_bu_posting','idx_report_execution_status_started_report',
        ] as $index) DB::statement('DROP INDEX IF EXISTS '.$index);
    }
};
