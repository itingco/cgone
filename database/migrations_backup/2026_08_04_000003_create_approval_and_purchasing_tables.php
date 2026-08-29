<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->decimal('amount', 20, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->unsignedSmallInteger('current_step')->default(1);
            $table->jsonb('required_roles');
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampsTz();
            $table->index(['approvable_type', 'approvable_id']);
        });

        Schema::create('approval_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('approval_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('step');
            $table->string('role_code', 50);
            $table->string('action', 20);
            $table->text('comment')->nullable();
            $table->foreignId('acted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('acted_at');
            $table->timestampsTz();
        });

        Schema::create('purchase_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->string('status', 30)->default('draft');
            $table->text('purpose');
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });

        Schema::create('purchase_request_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('estimated_unit_price', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestampsTz();
        });

        Schema::create('purchase_orders', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->date('expected_date')->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('received_quantity', 20, 6)->default(0);
            $table->decimal('invoiced_quantity', 20, 6)->default(0);
            $table->decimal('unit_price', 20, 2);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_rate', 9, 4)->default(0);
            $table->decimal('line_total', 20, 2);
            $table->timestampsTz();
        });

        Schema::create('supplier_advances', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->decimal('amount', 20, 2);
            $table->decimal('allocated_amount', 20, 2)->default(0);
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('supplier_advances')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });

        Schema::create('goods_receipts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->string('supplier_delivery_number')->nullable();
            $table->string('status', 30)->default('draft');
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('goods_receipts')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('base_quantity', 20, 6);
            $table->decimal('unit_cost', 20, 4);
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestampsTz();
        });

        Schema::create('supplier_invoices', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->string('supplier_invoice_number', 80);
            $table->date('document_date');
            $table->date('due_date');
            $table->string('status', 30)->default('draft');
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('advance_applied', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->decimal('outstanding_amount', 20, 2)->default(0);
            $table->jsonb('matching_result')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
            $table->unique(['company_id', 'supplier_id', 'supplier_invoice_number'], 'supplier_invoice_unique');
        });

        Schema::create('supplier_invoice_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('goods_receipt_line_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 6);
            $table->decimal('unit_price', 20, 2);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_rate', 9, 4)->default(0);
            $table->decimal('line_total', 20, 2);
            $table->jsonb('matching_result')->nullable();
            $table->timestampsTz();
        });

        Schema::create('supplier_advance_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_advance_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 20, 2);
            $table->timestampsTz();
            $table->unique(['supplier_advance_id', 'supplier_invoice_id'], 'supplier_advance_invoice_unique');
        });

        Schema::create('supplier_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->decimal('amount', 20, 2);
            $table->string('payment_method', 30);
            $table->string('bank_reference')->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('supplier_payments')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });
    }

    public function down(): void
    {
        foreach (['supplier_payments','supplier_advance_allocations','supplier_invoice_lines','supplier_invoices','goods_receipt_lines','goods_receipts','supplier_advances','purchase_order_lines','purchase_orders','purchase_request_lines','purchase_requests','approval_actions','approval_requests'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
