<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_ledger_applications')) {
            Schema::create('customer_ledger_applications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('source_credit_ledger_id')->constrained('customer_ledgers')->cascadeOnDelete();
                $t->foreignId('target_debit_ledger_id')->constrained('customer_ledgers')->cascadeOnDelete();
                $t->decimal('applied_amount',19,4);
                $t->timestamp('applied_at');
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->index(['source_credit_ledger_id','applied_at'],'customer_app_source_date_idx');
                $t->index(['target_debit_ledger_id','applied_at'],'customer_app_target_date_idx');
            });
        }

        if (! Schema::hasTable('vendor_ledger_applications')) {
            Schema::create('vendor_ledger_applications', function (Blueprint $t) {
                $t->id();
                $t->foreignId('source_debit_ledger_id')->constrained('vendor_ledgers')->cascadeOnDelete();
                $t->foreignId('target_credit_ledger_id')->constrained('vendor_ledgers')->cascadeOnDelete();
                $t->decimal('applied_amount',19,4);
                $t->timestamp('applied_at');
                $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $t->text('notes')->nullable();
                $t->timestamps();
                $t->index(['source_debit_ledger_id','applied_at'],'vendor_app_source_date_idx');
                $t->index(['target_credit_ledger_id','applied_at'],'vendor_app_target_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_ledger_applications');
        Schema::dropIfExists('customer_ledger_applications');
    }
};
