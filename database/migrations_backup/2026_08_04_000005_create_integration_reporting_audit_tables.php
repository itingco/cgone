<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('integration_sources', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->string('type', 30)->default('sales');
            $table->string('api_token_hash')->nullable();
            $table->boolean('auto_post')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('integration_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('integration_source_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('external_document_number', 100);
            $table->date('document_date');
            $table->string('document_type', 40)->default('sales_invoice');
            $table->string('status', 30)->default('staged');
            $table->string('customer_code', 50)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->jsonb('payload_hash_data')->nullable();
            $table->string('payload_hash', 64);
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['integration_source_id', 'external_document_number'], 'integration_document_idempotency');
        });

        Schema::create('integration_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('line_number');
            $table->string('item_code', 60);
            $table->string('uom_code', 20);
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 2);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2);
            $table->jsonb('raw_payload')->nullable();
            $table->timestampsTz();
            $table->unique(['integration_document_id', 'line_number']);
        });

        Schema::create('integration_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('integration_document_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('error_code', 60);
            $table->text('message');
            $table->jsonb('context')->default('{}');
            $table->string('status', 20)->default('open');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampsTz();
            $table->index(['company_id', 'status']);
        });

        Schema::create('report_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('data_source', 80);
            $table->jsonb('filters')->default('[]');
            $table->jsonb('columns')->default('[]');
            $table->jsonb('grouping')->default('[]');
            $table->jsonb('sorting')->default('[]');
            $table->boolean('is_system')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 60);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->jsonb('before_data')->nullable();
            $table->jsonb('after_data')->nullable();
            $table->string('before_hash', 64)->nullable();
            $table->string('after_hash', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs','report_definitions','integration_exceptions','integration_lines','integration_documents','integration_sources'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
