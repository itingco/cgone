<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('uoms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->restrictOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->unsignedSmallInteger('decimal_places')->default(0);
            $table->timestampsTz();
            $table->unique(['group_id', 'code']);
        });

        Schema::create('item_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('default_costing_method', 30)->default('moving_average');
            $table->timestampsTz();
            $table->unique(['group_id', 'code']);
        });

        Schema::create('items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('group_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('item_categories')->nullOnDelete();
            $table->foreignId('base_uom_id')->constrained('uoms')->restrictOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('item_type', 30)->default('stock');
            $table->string('scope', 20)->default('group');
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('attributes')->default('{}');
            $table->timestampsTz();
            $table->unique(['group_id', 'code']);
        });

        Schema::create('item_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 80)->unique();
            $table->string('name');
            $table->jsonb('attributes')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('item_uoms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('conversion_to_base', 18, 6);
            $table->unsignedSmallInteger('level')->default(1);
            $table->boolean('is_sales_uom')->default(true);
            $table->boolean('is_purchase_uom')->default(true);
            $table->timestampsTz();
            $table->unique(['item_id', 'uom_id']);
        });

        Schema::create('item_barcodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->string('barcode', 120)->unique();
            $table->timestampsTz();
        });

        Schema::create('price_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_level_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('tax_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('tax_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('item_costing_policies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_category_id')->nullable()->constrained('item_categories')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('costing_method', 30)->default('moving_average');
            $table->decimal('standard_cost', 20, 4)->nullable();
            $table->timestampTz('effective_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['company_id', 'item_id']);
        });

        Schema::create('price_change_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 60);
            $table->string('source_filename')->nullable();
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('total_lines')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['company_id', 'batch_number']);
        });

        Schema::create('price_change_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('batch_id')->constrained('price_change_batches')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_level_id')->constrained()->restrictOnDelete();
            $table->decimal('old_price', 20, 2)->nullable();
            $table->decimal('new_price', 20, 2);
            $table->timestampTz('effective_at');
            $table->text('reason');
            $table->string('status', 30)->default('pending_approval');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestampsTz();
            $table->index(['company_id', 'status', 'effective_at']);
        });

        Schema::create('item_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_level_id')->constrained()->restrictOnDelete();
            $table->foreignId('price_change_line_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 20, 2);
            $table->timestampTz('effective_at');
            $table->timestampTz('ended_at')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['company_id', 'item_id', 'uom_id', 'price_level_id', 'is_active'], 'item_prices_lookup_idx');
        });
    }

    public function down(): void
    {
        foreach (['item_prices','price_change_lines','price_change_batches','item_costing_policies','suppliers','customers','price_levels','item_barcodes','item_uoms','item_variants','items','item_categories','uoms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
