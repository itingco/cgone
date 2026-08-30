<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('business_units')) {
            Schema::create('business_units', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 150);
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });

            DB::table('business_units')->insert([
                'code' => 'MAIN',
                'name' => 'Main Business Unit',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('gl_batches') && ! Schema::hasColumn('gl_batches', 'business_unit_id')) {
            Schema::table('gl_batches', function (Blueprint $table): void {
                $table->foreignId('business_unit_id')
                    ->nullable()
                    ->constrained('business_units')
                    ->nullOnDelete();
                $table->index(['business_unit_id', 'posting_at'], 'gl_batches_bu_posting_idx');
            });

            $defaultBusinessUnitId = DB::table('business_units')
                ->where('is_default', true)
                ->orderBy('id')
                ->value('id');

            if ($defaultBusinessUnitId) {
                DB::table('gl_batches')
                    ->whereNull('business_unit_id')
                    ->update(['business_unit_id' => $defaultBusinessUnitId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('gl_batches') && Schema::hasColumn('gl_batches', 'business_unit_id')) {
            Schema::table('gl_batches', function (Blueprint $table): void {
                $table->dropIndex('gl_batches_bu_posting_idx');
                $table->dropConstrainedForeignId('business_unit_id');
            });
        }

        Schema::dropIfExists('business_units');
    }
};
