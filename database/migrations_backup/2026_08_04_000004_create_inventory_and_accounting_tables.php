<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedSmallInteger('period_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('closed_at')->nullable();
            $table->timestampsTz();
            $table->unique(['company_id', 'fiscal_year', 'period_number']);
        });

        Schema::create('accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('code', 40);
            $table->string('name');
            $table->string('account_type', 30);
            $table->string('normal_balance', 10);
            $table->string('financial_statement_group', 60);
            $table->boolean('allow_manual_posting')->default(true);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('posting_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('transaction_type', 60);
            $table->string('posting_key', 60);
            $table->foreignId('debit_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->foreignId('credit_account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            $table->jsonb('conditions')->default('{}');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->unique(['company_id', 'transaction_type', 'posting_key'], 'posting_profiles_unique');
        });

        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('fiscal_period_id')->constrained()->restrictOnDelete();
            $table->string('journal_number', 60);
            $table->date('journal_date');
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('status', 30)->default('posted');
            $table->text('description');
            $table->string('currency', 3)->default('IDR');
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('total_debit', 20, 2);
            $table->decimal('total_credit', 20, 2);
            $table->foreignId('posted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('posted_at');
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'journal_number']);
            $table->unique(['company_id', 'source_type', 'source_id'], 'journal_source_unique');
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->string('dimension_type')->nullable();
            $table->unsignedBigInteger('dimension_id')->nullable();
            $table->jsonb('dimensions')->default('{}');
            $table->timestampsTz();
            $table->index(['account_id', 'journal_entry_id']);
        });

        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->string('transaction_type', 50);
            $table->string('source_type', 80);
            $table->unsignedBigInteger('source_id');
            $table->string('status', 30)->default('posted');
            $table->foreignId('posted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('posted_at');
            $table->foreignId('reversal_of_id')->nullable()->constrained('inventory_transactions')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
            $table->unique(['company_id', 'source_type', 'source_id'], 'inventory_source_unique');
        });

        Schema::create('inventory_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_in', 20, 6)->default(0);
            $table->decimal('quantity_out', 20, 6)->default(0);
            $table->decimal('unit_cost', 20, 4);
            $table->decimal('total_cost', 20, 2);
            $table->decimal('running_quantity', 20, 6);
            $table->decimal('running_value', 20, 2);
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestampTz('posted_at');
            $table->timestampsTz();
            $table->index(['company_id', 'warehouse_id', 'item_id', 'posted_at'], 'inventory_ledger_lookup_idx');
        });

        Schema::create('budgets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedSmallInteger('period_number');
            $table->decimal('amount', 20, 2);
            $table->string('status', 20)->default('approved');
            $table->timestampsTz();
            $table->unique(['company_id', 'account_id', 'fiscal_year', 'period_number'], 'budgets_unique');
        });
    }

    public function down(): void
    {
        foreach (['budgets','inventory_ledger','inventory_transactions','journal_lines','journal_entries','posting_profiles','accounts','fiscal_periods'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
