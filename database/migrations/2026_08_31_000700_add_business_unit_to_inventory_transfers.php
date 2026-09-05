<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['goods_transfer_requests','goods_transfers'] as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table,'business_unit_id')) {
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
        foreach (['goods_transfers','goods_transfer_requests'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table,'business_unit_id')) {
                Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('business_unit_id'));
            }
        }
    }
};
