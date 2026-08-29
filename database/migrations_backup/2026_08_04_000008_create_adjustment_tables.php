<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('journal_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('source_journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->string('adjustment_type', 30)->default('manual');
            $table->text('reason');
            $table->string('status', 30)->default('draft');
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });

        Schema::create('journal_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('journal_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->jsonb('dimensions')->default('{}');
            $table->timestampsTz();
        });

        Schema::create('inventory_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_inventory_transaction_id')->nullable()->constrained('inventory_transactions')->restrictOnDelete();
            $table->string('document_number', 60);
            $table->date('document_date');
            $table->text('reason');
            $table->string('status', 30)->default('draft');
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('posted_at')->nullable();
            $table->timestampsTz();
            $table->unique(['company_id', 'document_number']);
        });

        Schema::create('inventory_adjustment_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('uom_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity_delta', 20, 6);
            $table->decimal('unit_cost', 20, 4);
            $table->string('batch_number')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestampsTz();
        });

        foreach (['journal_adjustments', 'inventory_adjustments'] as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY {$table}_company_policy ON {$table} USING (company_id = erp_current_company_id()) WITH CHECK (company_id = erp_current_company_id())");
            DB::statement("CREATE TRIGGER {$table}_immutable_posted BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION erp_prevent_posted_mutation()");
        }

        foreach ([
            'journal_adjustment_lines' => ['journal_adjustments', 'journal_adjustment_id'],
            'inventory_adjustment_lines' => ['inventory_adjustments', 'inventory_adjustment_id'],
        ] as $table => [$parent, $foreignKey]) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            $expression = "EXISTS (SELECT 1 FROM {$parent} p WHERE p.id = {$table}.{$foreignKey} AND p.company_id = erp_current_company_id())";
            DB::statement("CREATE POLICY {$table}_parent_policy ON {$table} USING ({$expression}) WITH CHECK ({$expression})");
        }
    }

    public function down(): void
    {
        foreach (['journal_adjustments', 'inventory_adjustments'] as $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$table}_immutable_posted ON {$table}");
        }
        Schema::dropIfExists('inventory_adjustment_lines');
        Schema::dropIfExists('inventory_adjustments');
        Schema::dropIfExists('journal_adjustment_lines');
        Schema::dropIfExists('journal_adjustments');
    }
};
