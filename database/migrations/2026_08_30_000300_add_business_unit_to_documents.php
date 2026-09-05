<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int,string> */
    private array $tables = [
        'sales_requests',
        'sales_orders',
        'shipments',
        'sales_invoices',
        'purchase_requests',
        'purchase_orders',
        'receipts',
        'purchase_invoices',
        'posted_shipments',
        'posted_sales_invoices',
        'posted_receipts',
        'posted_purchase_invoices',
        'item_ledgers',
        'customer_ledgers',
        'vendor_ledgers',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('business_units')) {
            return;
        }

        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'business_unit_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('business_unit_id')
                    ->nullable()
                    ->constrained('business_units')
                    ->nullOnDelete();
                $t->index('business_unit_id');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'business_unit_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('business_unit_id');
            });
        }
    }
};
