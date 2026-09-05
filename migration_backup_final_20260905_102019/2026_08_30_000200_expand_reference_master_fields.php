<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('items', function (Blueprint $t) {
            $t->string('class_code', 50)->nullable();
            $t->string('family', 120)->nullable();
            $t->string('sub_family', 120)->nullable();
            $t->string('manufacturer', 150)->nullable();
            $t->string('price_group', 100)->nullable();
            $t->string('incentive_schedule', 100)->nullable();
            $t->string('size_label', 100)->nullable();
            $t->string('color_label', 100)->nullable();
            $t->decimal('size_ratio', 19, 6)->nullable();

            $t->decimal('length', 19, 4)->default(0);
            $t->decimal('width', 19, 4)->default(0);
            $t->decimal('thickness', 19, 4)->default(0);
            $t->decimal('net_weight', 19, 4)->default(0);
            $t->decimal('gross_weight', 19, 4)->default(0);
            $t->decimal('volume', 19, 6)->default(0);

            $t->string('order_interval', 50)->nullable();
            $t->decimal('order_multiple', 19, 4)->default(0);
            $t->decimal('sales_min_order', 19, 4)->default(0);
            $t->unsignedInteger('lifetime_months')->default(0);
            $t->decimal('min_markup_pct', 9, 4)->default(0);
            $t->decimal('max_markup_pct', 9, 4)->default(0);

            $t->boolean('require_serial_numbers')->default(false);
            $t->boolean('unique_serial_number')->default(false);
            $t->boolean('auto_uom_conversion')->default(false);
            $t->boolean('ignore_cost_verification')->default(false);
            $t->boolean('consignment_goods')->default(false);
            $t->boolean('no_tax')->default(false);
            $t->boolean('allow_sales_bonus')->default(false);
            $t->boolean('purchase_sales_by_weight')->default(false);
            $t->boolean('can_be_used')->default(true);
            $t->boolean('is_confidential')->default(false);
            $t->unsignedInteger('warranty_supplier_months')->default(0);
            $t->unsignedInteger('warranty_customer_months')->default(0);
            $t->decimal('reward_points', 19, 4)->default(0);
            $t->foreignId('default_warehouse_id')->nullable()->constrained('warehouses');
            $t->string('grade', 50)->nullable();
        });

        Schema::create('item_aliases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->string('alias_code', 150)->unique();
            $t->string('alias_type', 30)->default('BARCODE');
            $t->foreignId('uom_id')->nullable()->constrained('uoms');
            $t->string('description')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['item_id', 'alias_type', 'is_active']);
        });

        Schema::table('customers', function (Blueprint $t) {
            $t->string('billing_address_line_2')->nullable();
            $t->string('billing_kelurahan')->nullable();
            $t->string('billing_kecamatan')->nullable();
            $t->string('billing_kabupaten')->nullable();
            $t->string('billing_postal_code', 30)->nullable();
            $t->string('shipping_address_line_2')->nullable();
            $t->string('shipping_kelurahan')->nullable();
            $t->string('shipping_kecamatan')->nullable();
            $t->string('shipping_kabupaten')->nullable();
            $t->string('shipping_postal_code', 30)->nullable();
            $t->string('fax', 50)->nullable();
            $t->string('website')->nullable();
            $t->string('finance_tax_contact')->nullable();
            $t->string('nppkp', 80)->nullable();
            $t->string('id_card_no', 100)->nullable();
            $t->string('virtual_account', 100)->nullable();
            $t->string('owner_name')->nullable();
            $t->unsignedSmallInteger('year_established')->nullable();
            $t->date('customer_since')->nullable();
            $t->string('external_code', 100)->nullable();
            $t->string('external_code_2', 100)->nullable();
            $t->text('flags')->nullable();
            $t->text('remarks')->nullable();

            $t->string('currency_code', 10)->default('IDR');
            $t->boolean('no_credit')->default(false);
            $t->decimal('max_invoice_amount', 19, 4)->default(0);
            $t->unsignedInteger('max_invoices')->default(0);
            $t->unsignedInteger('payment_additional_days')->default(0);
            $t->string('due_date_origin', 30)->default('INVOICE_DATE');
            $t->unsignedInteger('so_delivery_term_days')->default(0);
            $t->unsignedInteger('arrival_days')->default(0);
            $t->decimal('default_discount_pct', 9, 4)->default(0);
            $t->decimal('extra_discount_pct', 9, 4)->default(0);
            $t->decimal('default_tax_pct', 9, 4)->default(0);
            $t->boolean('tax_inclusive')->default(false);
            $t->string('sales_person_name')->nullable();
            $t->string('fob', 50)->nullable();
            $t->string('shipper')->nullable();
            $t->string('collector')->nullable();
            $t->decimal('surcharge_pct', 9, 4)->default(0);
            $t->string('customer_type', 100)->nullable();
            $t->string('business_group', 100)->nullable();
            $t->string('area', 100)->nullable();
            $t->string('sub_area', 100)->nullable();
            $t->string('channel', 100)->nullable();
            $t->string('rank', 100)->nullable();
            $t->string('scale', 100)->nullable();
            $t->string('status_code', 100)->nullable();
            $t->text('discount_rules')->nullable();
            $t->decimal('so_down_payment_pct', 9, 4)->default(0);
            $t->unsignedInteger('cash_top_days')->default(0);

            $t->boolean('always_require_so')->default(false);
            $t->boolean('allow_nonpurchase_sn_return')->default(false);
            $t->boolean('allow_partial_shipment')->default(true);
            $t->boolean('prospect')->default(false);
            $t->boolean('sales_order_finance_approval')->default(false);
            $t->boolean('spb_finance_approval')->default(false);
            $t->boolean('skip_overlimit_check')->default(false);
            $t->boolean('tax_not_paid')->default(false);
            $t->boolean('is_manufacturer')->default(false);
            $t->boolean('is_supplier')->default(false);
            $t->decimal('extra_bruto', 19, 4)->default(0);
        });

        Schema::table('vendors', function (Blueprint $t) {
            $t->string('zip_code', 30)->nullable();
            $t->string('fax', 50)->nullable();
            $t->string('confirm_to')->nullable();
            $t->string('website')->nullable();
            $t->string('id_card_no', 100)->nullable();
            $t->string('nppkp', 80)->nullable();
            $t->string('vendor_type', 100)->nullable();
            $t->string('initial', 50)->nullable();
            $t->string('vendor_group', 100)->nullable();
            $t->string('virtual_account', 100)->nullable();
            $t->string('ep_name')->nullable();
            $t->text('flags')->nullable();
            $t->text('remarks')->nullable();
            $t->unsignedInteger('visit_interval')->default(0);

            $t->string('currency_code', 10)->default('IDR');
            $t->string('payment_due_origin', 30)->default('INVOICE_DATE');
            $t->string('fob', 50)->nullable();
            $t->string('shipper')->nullable();
            $t->decimal('down_payment_pct', 9, 4)->default(0);
            $t->decimal('default_discount_pct', 9, 4)->default(0);
            $t->decimal('default_tax_pct', 9, 4)->default(0);
            $t->boolean('tax_inclusive')->default(false);
            $t->string('pph_code', 30)->nullable();
            $t->text('bank_address')->nullable();
            $t->string('payment_instruction')->nullable();
            $t->boolean('always_require_po')->default(false);
            $t->boolean('is_on_hold')->default(false);
            $t->boolean('is_confidential')->default(false);
            $t->date('registered_since')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $t) {
            $t->dropColumn([
                'zip_code','fax','confirm_to','website','id_card_no','nppkp','vendor_type','initial','vendor_group','virtual_account','ep_name','flags','remarks','visit_interval',
                'currency_code','payment_due_origin','fob','shipper','down_payment_pct','default_discount_pct','default_tax_pct','tax_inclusive','pph_code','bank_address','payment_instruction','always_require_po','is_on_hold','is_confidential','registered_since',
            ]);
        });

        Schema::table('customers', function (Blueprint $t) {
            $t->dropColumn([
                'billing_address_line_2','billing_kelurahan','billing_kecamatan','billing_kabupaten','billing_postal_code','shipping_address_line_2','shipping_kelurahan','shipping_kecamatan','shipping_kabupaten','shipping_postal_code',
                'fax','website','finance_tax_contact','nppkp','id_card_no','virtual_account','owner_name','year_established','customer_since','external_code','external_code_2','flags','remarks','currency_code','no_credit','max_invoice_amount','max_invoices','payment_additional_days','due_date_origin','so_delivery_term_days','arrival_days','default_discount_pct','extra_discount_pct','default_tax_pct','tax_inclusive','sales_person_name','fob','shipper','collector','surcharge_pct','customer_type','business_group','area','sub_area','channel','rank','scale','status_code','discount_rules','so_down_payment_pct','cash_top_days','always_require_so','allow_nonpurchase_sn_return','allow_partial_shipment','prospect','sales_order_finance_approval','spb_finance_approval','skip_overlimit_check','tax_not_paid','is_manufacturer','is_supplier','extra_bruto',
            ]);
        });

        Schema::dropIfExists('item_aliases');
        Schema::table('items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('default_warehouse_id');
            $t->dropColumn([
                'class_code','family','sub_family','manufacturer','price_group','incentive_schedule','size_label','color_label','size_ratio','length','width','thickness','net_weight','gross_weight','volume','order_interval','order_multiple','sales_min_order','lifetime_months','min_markup_pct','max_markup_pct','require_serial_numbers','unique_serial_number','auto_uom_conversion','ignore_cost_verification','consignment_goods','no_tax','allow_sales_bonus','purchase_sales_by_weight','can_be_used','is_confidential','warranty_supplier_months','warranty_customer_months','reward_points','grade',
            ]);
        });
    }
};
