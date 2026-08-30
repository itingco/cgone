<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addItems();
        $this->addCustomers();
        $this->addVendors();
        $this->addAccounts();
    }

    private function addItems(): void
    {
        if (! Schema::hasTable('items')) return;
        Schema::table('items', function (Blueprint $t) {
            if (! Schema::hasColumn('items', 'short_name')) $t->string('short_name')->nullable();
            if (! Schema::hasColumn('items', 'barcode')) $t->string('barcode', 100)->nullable();
            if (! Schema::hasColumn('items', 'manufacturer_code')) $t->string('manufacturer_code', 100)->nullable();
            if (! Schema::hasColumn('items', 'model_no')) $t->string('model_no', 100)->nullable();
            if (! Schema::hasColumn('items', 'origin_country')) $t->string('origin_country', 100)->nullable();
            if (! Schema::hasColumn('items', 'hs_code')) $t->string('hs_code', 50)->nullable();
            if (! Schema::hasColumn('items', 'warranty_months')) $t->unsignedInteger('warranty_months')->default(0);
            if (! Schema::hasColumn('items', 'weight')) $t->decimal('weight', 19, 4)->default(0);
            if (! Schema::hasColumn('items', 'length')) $t->decimal('length', 19, 4)->default(0);
            if (! Schema::hasColumn('items', 'width')) $t->decimal('width', 19, 4)->default(0);
            if (! Schema::hasColumn('items', 'height')) $t->decimal('height', 19, 4)->default(0);
            if (! Schema::hasColumn('items', 'track_serial')) $t->boolean('track_serial')->default(false);
            if (! Schema::hasColumn('items', 'track_batch')) $t->boolean('track_batch')->default(false);
            if (! Schema::hasColumn('items', 'purchase_description')) $t->text('purchase_description')->nullable();
            if (! Schema::hasColumn('items', 'sales_description')) $t->text('sales_description')->nullable();
        });
    }

    private function addCustomers(): void
    {
        if (! Schema::hasTable('customers')) return;
        Schema::table('customers', function (Blueprint $t) {
            if (! Schema::hasColumn('customers', 'contact_person')) $t->string('contact_person')->nullable();
            if (! Schema::hasColumn('customers', 'mobile')) $t->string('mobile', 50)->nullable();
            if (! Schema::hasColumn('customers', 'fax')) $t->string('fax', 50)->nullable();
            if (! Schema::hasColumn('customers', 'website')) $t->string('website')->nullable();
            if (! Schema::hasColumn('customers', 'billing_postal_code')) $t->string('billing_postal_code', 30)->nullable();
            if (! Schema::hasColumn('customers', 'shipping_postal_code')) $t->string('shipping_postal_code', 30)->nullable();
            if (! Schema::hasColumn('customers', 'currency_code')) $t->string('currency_code', 10)->default('IDR');
            if (! Schema::hasColumn('customers', 'salesperson_code')) $t->string('salesperson_code', 100)->nullable();
            if (! Schema::hasColumn('customers', 'tax_name')) $t->string('tax_name')->nullable();
            if (! Schema::hasColumn('customers', 'is_pkp')) $t->boolean('is_pkp')->default(false);
            if (! Schema::hasColumn('customers', 'credit_hold')) $t->boolean('credit_hold')->default(false);
            if (! Schema::hasColumn('customers', 'notes')) $t->text('notes')->nullable();
        });
    }

    private function addVendors(): void
    {
        if (! Schema::hasTable('vendors')) return;
        Schema::table('vendors', function (Blueprint $t) {
            if (! Schema::hasColumn('vendors', 'contact_person')) $t->string('contact_person')->nullable();
            if (! Schema::hasColumn('vendors', 'mobile')) $t->string('mobile', 50)->nullable();
            if (! Schema::hasColumn('vendors', 'fax')) $t->string('fax', 50)->nullable();
            if (! Schema::hasColumn('vendors', 'website')) $t->string('website')->nullable();
            if (! Schema::hasColumn('vendors', 'postal_code')) $t->string('postal_code', 30)->nullable();
            if (! Schema::hasColumn('vendors', 'currency_code')) $t->string('currency_code', 10)->default('IDR');
            if (! Schema::hasColumn('vendors', 'lead_time_days')) $t->unsignedInteger('lead_time_days')->default(0);
            if (! Schema::hasColumn('vendors', 'min_order_value')) $t->decimal('min_order_value', 19, 4)->default(0);
            if (! Schema::hasColumn('vendors', 'tax_name')) $t->string('tax_name')->nullable();
            if (! Schema::hasColumn('vendors', 'is_pkp')) $t->boolean('is_pkp')->default(false);
            if (! Schema::hasColumn('vendors', 'purchase_hold')) $t->boolean('purchase_hold')->default(false);
            if (! Schema::hasColumn('vendors', 'notes')) $t->text('notes')->nullable();
        });
    }

    private function addAccounts(): void
    {
        if (! Schema::hasTable('chart_of_accounts')) return;
        Schema::table('chart_of_accounts', function (Blueprint $t) {
            if (! Schema::hasColumn('chart_of_accounts', 'account_subcategory')) $t->string('account_subcategory', 100)->nullable();
            if (! Schema::hasColumn('chart_of_accounts', 'report_group')) $t->string('report_group', 100)->nullable();
            if (! Schema::hasColumn('chart_of_accounts', 'cash_flow_category')) $t->string('cash_flow_category', 100)->nullable();
            if (! Schema::hasColumn('chart_of_accounts', 'external_code')) $t->string('external_code', 100)->nullable();
            if (! Schema::hasColumn('chart_of_accounts', 'is_control_account')) $t->boolean('is_control_account')->default(false);
            if (! Schema::hasColumn('chart_of_accounts', 'reconciliation_required')) $t->boolean('reconciliation_required')->default(false);
            if (! Schema::hasColumn('chart_of_accounts', 'notes')) $t->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        $this->dropColumns('items', ['short_name','barcode','manufacturer_code','model_no','origin_country','hs_code','warranty_months','weight','length','width','height','track_serial','track_batch','purchase_description','sales_description']);
        $this->dropColumns('customers', ['contact_person','mobile','fax','website','billing_postal_code','shipping_postal_code','currency_code','salesperson_code','tax_name','is_pkp','credit_hold','notes']);
        $this->dropColumns('vendors', ['contact_person','mobile','fax','website','postal_code','currency_code','lead_time_days','min_order_value','tax_name','is_pkp','purchase_hold','notes']);
        $this->dropColumns('chart_of_accounts', ['account_subcategory','report_group','cash_flow_category','external_code','is_control_account','reconciliation_required','notes']);
    }

    private function dropColumns(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) return;
        $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($table, $column)));
        if ($existing) Schema::table($table, fn (Blueprint $t) => $t->dropColumn($existing));
    }
};
