<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_cost_layers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_ledger_id')->constrained('inventory_ledger')->restrictOnDelete();
            $table->decimal('quantity_initial', 20, 6);
            $table->decimal('quantity_remaining', 20, 6);
            $table->decimal('unit_cost', 20, 4);
            $table->date('received_date');
            $table->timestampsTz();
            $table->index(['company_id', 'warehouse_id', 'item_id', 'received_date'], 'inventory_fifo_lookup_idx');
        });

        Schema::create('sales_summary_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('integration_source_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('summary_date');
            $table->string('payment_method', 40)->default('unknown');
            $table->unsignedInteger('document_count');
            $table->decimal('subtotal', 20, 2);
            $table->decimal('tax_amount', 20, 2);
            $table->decimal('total_amount', 20, 2);
            $table->decimal('cogs_amount', 20, 2)->default(0);
            $table->string('status', 30)->default('posted');
            $table->foreignId('journal_entry_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('posted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('posted_at');
            $table->timestampsTz();
        });

        DB::statement("CREATE UNIQUE INDEX sales_summary_batch_unique ON sales_summary_batches (company_id, integration_source_id, summary_date, COALESCE(branch_id,0), COALESCE(warehouse_id,0), payment_method)");

        foreach (['inventory_cost_layers', 'sales_summary_batches'] as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("CREATE POLICY {$table}_company_policy ON {$table} USING (company_id = erp_current_company_id()) WITH CHECK (company_id = erp_current_company_id())");
        }

        DB::statement('CREATE TRIGGER sales_summary_batches_immutable_posted BEFORE UPDATE OR DELETE ON sales_summary_batches FOR EACH ROW EXECUTE FUNCTION erp_prevent_posted_mutation()');
        DB::statement('CREATE TRIGGER integration_documents_immutable_posted BEFORE UPDATE OR DELETE ON integration_documents FOR EACH ROW EXECUTE FUNCTION erp_prevent_posted_mutation()');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS integration_documents_immutable_posted ON integration_documents');
        DB::statement('DROP TRIGGER IF EXISTS sales_summary_batches_immutable_posted ON sales_summary_batches');
        Schema::dropIfExists('sales_summary_batches');
        Schema::dropIfExists('inventory_cost_layers');
    }
};
