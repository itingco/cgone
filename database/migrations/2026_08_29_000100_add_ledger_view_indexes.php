<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->index('posting_at', 'idx_item_ledgers_posting_at');
            $table->index(['location_id', 'posting_at'], 'idx_item_ledgers_location_posting');
            $table->index(['bin_id', 'posting_at'], 'idx_item_ledgers_bin_posting');
        });

        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->index('posting_at', 'idx_customer_ledgers_posting_at');
        });

        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->index('posting_at', 'idx_vendor_ledgers_posting_at');
        });

        Schema::table('gl_batches', function (Blueprint $table) {
            $table->index('posting_at', 'idx_gl_batches_posting_at');
        });
    }

    public function down(): void
    {
        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_item_ledgers_posting_at');
            $table->dropIndex('idx_item_ledgers_location_posting');
            $table->dropIndex('idx_item_ledgers_bin_posting');
        });

        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_customer_ledgers_posting_at');
        });

        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_vendor_ledgers_posting_at');
        });

        Schema::table('gl_batches', function (Blueprint $table) {
            $table->dropIndex('idx_gl_batches_posting_at');
        });
    }
};
