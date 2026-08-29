<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function commonHeader(Blueprint $t): void {
        $t->id(); $t->string('document_no',120)->unique(); $t->date('document_date'); $t->string('status',20)->default('OPEN');
        $t->string('currency_code',10)->default('IDR'); $t->decimal('subtotal',19,4)->default(0); $t->decimal('discount_total',19,4)->default(0); $t->decimal('tax_total',19,4)->default(0); $t->decimal('grand_total',19,4)->default(0);
        $t->text('notes')->nullable(); $t->foreignId('created_by')->constrained('users'); $t->foreignId('released_by')->nullable()->constrained('users'); $t->timestamp('released_at')->nullable();
        $t->foreignId('posted_by')->nullable()->constrained('users'); $t->timestamp('posted_at')->nullable(); $t->unsignedBigInteger('posted_document_id')->nullable(); $t->timestamp('undone_at')->nullable(); $t->foreignId('undone_by')->nullable()->constrained('users'); $t->timestamps(); $t->index(['status','document_date']);
    }
    private function salesHeader(Blueprint $t): void { $this->commonHeader($t); $t->foreignId('customer_id')->constrained('customers'); $t->foreignId('warehouse_id')->nullable()->constrained('warehouses'); }
    private function purchaseHeader(Blueprint $t): void { $this->commonHeader($t); $t->foreignId('vendor_id')->constrained('vendors'); $t->foreignId('warehouse_id')->nullable()->constrained('warehouses'); }
    private function commonLine(Blueprint $t): void { $t->id(); $t->foreignId('item_id')->constrained('items'); $t->string('description')->nullable(); $t->decimal('quantity',19,4); $t->decimal('unit_price',19,4)->default(0); $t->decimal('unit_cost',19,4)->default(0); $t->decimal('discount_amount',19,4)->default(0); $t->decimal('tax_rate',9,4)->default(0); $t->decimal('tax_amount',19,4)->default(0); $t->decimal('line_total',19,4)->default(0); $t->timestamps(); }
    public function up(): void {
        Schema::create('sales_requests', fn(Blueprint $t)=>$this->salesHeader($t));
        Schema::create('sales_request_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('sales_request_id')->constrained()->cascadeOnDelete();});
        Schema::create('sales_orders', function(Blueprint $t){$this->salesHeader($t);$t->foreignId('source_sales_request_id')->nullable()->constrained('sales_requests');});
        Schema::create('sales_order_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('sales_order_id')->constrained()->cascadeOnDelete();$t->foreignId('source_sales_request_line_id')->nullable()->constrained('sales_request_lines');});
        Schema::create('shipments', function(Blueprint $t){$this->salesHeader($t);$t->foreignId('source_sales_order_id')->constrained('sales_orders');});
        Schema::create('shipment_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('shipment_id')->constrained()->cascadeOnDelete();$t->foreignId('source_sales_order_line_id')->constrained('sales_order_lines');});
        Schema::create('sales_invoices', function(Blueprint $t){$this->salesHeader($t);$t->foreignId('source_sales_order_id')->nullable()->constrained('sales_orders');});
        Schema::create('sales_invoice_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('sales_invoice_id')->constrained()->cascadeOnDelete();$t->foreignId('source_posted_shipment_line_id')->nullable();$t->foreignId('source_sales_order_line_id')->nullable()->constrained('sales_order_lines');$t->boolean('direct_service')->default(false);});

        Schema::create('purchase_requests', fn(Blueprint $t)=>$this->purchaseHeader($t));
        Schema::create('purchase_request_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();});
        Schema::create('purchase_orders', function(Blueprint $t){$this->purchaseHeader($t);$t->foreignId('source_purchase_request_id')->nullable()->constrained('purchase_requests');});
        Schema::create('purchase_order_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();$t->foreignId('source_purchase_request_line_id')->nullable()->constrained('purchase_request_lines');});
        Schema::create('receipts', function(Blueprint $t){$this->purchaseHeader($t);$t->foreignId('source_purchase_order_id')->constrained('purchase_orders');});
        Schema::create('receipt_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('receipt_id')->constrained()->cascadeOnDelete();$t->foreignId('source_purchase_order_line_id')->constrained('purchase_order_lines');});
        Schema::create('purchase_invoices', function(Blueprint $t){$this->purchaseHeader($t);$t->foreignId('source_purchase_order_id')->nullable()->constrained('purchase_orders');});
        Schema::create('purchase_invoice_lines', function(Blueprint $t){$this->commonLine($t);$t->foreignId('purchase_invoice_id')->constrained()->cascadeOnDelete();$t->foreignId('source_posted_receipt_line_id')->nullable();$t->foreignId('source_purchase_order_line_id')->nullable()->constrained('purchase_order_lines');$t->boolean('direct_service')->default(false);});
    }
    public function down(): void { foreach(['purchase_invoice_lines','purchase_invoices','receipt_lines','receipts','purchase_order_lines','purchase_orders','purchase_request_lines','purchase_requests','sales_invoice_lines','sales_invoices','shipment_lines','shipments','sales_order_lines','sales_orders','sales_request_lines','sales_requests'] as $table) Schema::dropIfExists($table); }
};
