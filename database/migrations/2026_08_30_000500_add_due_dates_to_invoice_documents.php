<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sales_invoices','purchase_invoices','posted_sales_invoices','posted_purchase_invoices'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'due_date')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->date('due_date')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(['sales_invoices','purchase_invoices','posted_sales_invoices','posted_purchase_invoices']) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'due_date')) {
                continue;
            }

            Schema::table($table, fn (Blueprint $t) => $t->dropColumn('due_date'));
        }
    }
};
