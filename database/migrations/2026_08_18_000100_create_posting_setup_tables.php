<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('inventory_posting_groups', function (Blueprint $t) {
            $t->id(); $t->string('code',50)->unique(); $t->string('name');
            $t->foreignId('inventory_account_id')->constrained('chart_of_accounts');
            $t->foreignId('cogs_account_id')->constrained('chart_of_accounts');
            $t->foreignId('adjustment_account_id')->nullable()->constrained('chart_of_accounts');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('general_product_posting_groups', function (Blueprint $t) {
            $t->id(); $t->string('code',50)->unique(); $t->string('name');
            $t->foreignId('sales_account_id')->constrained('chart_of_accounts');
            $t->foreignId('purchase_account_id')->constrained('chart_of_accounts');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('customer_posting_groups', function (Blueprint $t) {
            $t->id(); $t->string('code',50)->unique(); $t->string('name');
            $t->foreignId('receivable_account_id')->constrained('chart_of_accounts');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('vendor_posting_groups', function (Blueprint $t) {
            $t->id(); $t->string('code',50)->unique(); $t->string('name');
            $t->foreignId('payable_account_id')->constrained('chart_of_accounts');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('tax_posting_groups', function (Blueprint $t) {
            $t->id(); $t->string('code',50)->unique(); $t->string('name');
            $t->decimal('rate',9,4)->default(0);
            $t->foreignId('output_tax_account_id')->nullable()->constrained('chart_of_accounts');
            $t->foreignId('input_tax_account_id')->nullable()->constrained('chart_of_accounts');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('posting_setups', function (Blueprint $t) {
            $t->id(); $t->string('code',50)->unique()->default('DEFAULT'); $t->string('name')->default('Default Posting Setup');
            $t->foreignId('grni_account_id')->constrained('chart_of_accounts');
            $t->foreignId('inventory_adjustment_account_id')->nullable()->constrained('chart_of_accounts');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::table('items', function (Blueprint $t) {
            $t->foreignId('inventory_posting_group_id')->nullable()->constrained('inventory_posting_groups');
            $t->foreignId('general_product_posting_group_id')->nullable()->constrained('general_product_posting_groups');
            $t->foreignId('tax_posting_group_id')->nullable()->constrained('tax_posting_groups');
        });
        Schema::table('customers', function (Blueprint $t) {
            $t->foreignId('customer_posting_group_id')->nullable()->constrained('customer_posting_groups');
            $t->foreignId('tax_posting_group_id')->nullable()->constrained('tax_posting_groups');
        });
        Schema::table('vendors', function (Blueprint $t) {
            $t->foreignId('vendor_posting_group_id')->nullable()->constrained('vendor_posting_groups');
            $t->foreignId('tax_posting_group_id')->nullable()->constrained('tax_posting_groups');
        });
        Schema::table('document_sequences', function (Blueprint $t) {
            $t->string('format_pattern',120)->nullable();
        });
    }
    public function down(): void {
        Schema::table('document_sequences', fn(Blueprint $t)=>$t->dropColumn('format_pattern'));
        Schema::table('vendors', fn(Blueprint $t)=>$t->dropConstrainedForeignId('tax_posting_group_id'));
        Schema::table('vendors', fn(Blueprint $t)=>$t->dropConstrainedForeignId('vendor_posting_group_id'));
        Schema::table('customers', fn(Blueprint $t)=>$t->dropConstrainedForeignId('tax_posting_group_id'));
        Schema::table('customers', fn(Blueprint $t)=>$t->dropConstrainedForeignId('customer_posting_group_id'));
        Schema::table('items', fn(Blueprint $t)=>$t->dropConstrainedForeignId('tax_posting_group_id'));
        Schema::table('items', fn(Blueprint $t)=>$t->dropConstrainedForeignId('general_product_posting_group_id'));
        Schema::table('items', fn(Blueprint $t)=>$t->dropConstrainedForeignId('inventory_posting_group_id'));
        foreach(['posting_setups','tax_posting_groups','vendor_posting_groups','customer_posting_groups','general_product_posting_groups','inventory_posting_groups'] as $table) Schema::dropIfExists($table);
    }
};
