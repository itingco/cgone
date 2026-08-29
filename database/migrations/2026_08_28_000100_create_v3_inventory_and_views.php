<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('data_views', function (Blueprint $t) {
            $t->id(); $t->string('module_key',120); $t->string('name',120); $t->string('scope',20)->default('personal');
            $t->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete(); $t->boolean('is_system')->default(false); $t->boolean('is_active')->default(true);
            $t->json('columns_json')->nullable(); $t->json('filters_json')->nullable(); $t->json('sort_json')->nullable(); $t->string('filter_mode',3)->default('AND'); $t->unsignedSmallInteger('page_size')->default(50);
            $t->foreignId('created_by')->constrained('users'); $t->foreignId('updated_by')->nullable()->constrained('users'); $t->timestamps();
            $t->index(['module_key','scope','user_id']);
        });
        Schema::create('user_view_preferences', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained('users')->cascadeOnDelete(); $t->string('module_key',120); $t->foreignId('data_view_id')->constrained('data_views')->cascadeOnDelete(); $t->timestamps();
            $t->unique(['user_id','module_key']);
        });
        Schema::create('locations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('legacy_warehouse_id')->nullable()->index(); $t->string('code',50)->unique(); $t->string('name'); $t->text('address')->nullable();
            $t->boolean('bin_mandatory')->default(false); $t->decimal('additional_discount_pct',9,4)->default(0); $t->boolean('is_system')->default(false); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('location_bins', function (Blueprint $t) {
            $t->id(); $t->foreignId('location_id')->constrained('locations')->cascadeOnDelete(); $t->string('code',50); $t->string('name'); $t->text('description')->nullable();
            $t->decimal('additional_discount_pct',9,4)->default(0); $t->boolean('is_active')->default(true); $t->timestamps(); $t->unique(['location_id','code']);
        });

        if (Schema::hasTable('warehouses')) {
            foreach (DB::table('warehouses')->orderBy('id')->get() as $w) {
                DB::table('locations')->updateOrInsert(['code'=>$w->code], ['legacy_warehouse_id'=>$w->id,'name'=>$w->name,'address'=>$w->address,'bin_mandatory'=>false,'additional_discount_pct'=>0,'is_system'=>false,'is_active'=>$w->is_active,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
        DB::table('locations')->updateOrInsert(['code'=>'IN-TRANSIT'], ['name'=>'Inventory In Transit','bin_mandatory'=>false,'additional_discount_pct'=>0,'is_system'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);

        // V3 stock postings use Location/Bin. Keep legacy Warehouse links nullable for historical compatibility.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE item_ledgers ALTER COLUMN warehouse_id DROP NOT NULL');
            DB::statement('ALTER TABLE posted_shipments ALTER COLUMN warehouse_id DROP NOT NULL');
            DB::statement('ALTER TABLE posted_receipts ALTER COLUMN warehouse_id DROP NOT NULL');
        } else {
            Schema::table('item_ledgers', fn(Blueprint $t) => $t->unsignedBigInteger('warehouse_id')->nullable()->change());
            Schema::table('posted_shipments', fn(Blueprint $t) => $t->unsignedBigInteger('warehouse_id')->nullable()->change());
            Schema::table('posted_receipts', fn(Blueprint $t) => $t->unsignedBigInteger('warehouse_id')->nullable()->change());
        }

        foreach (['sales_requests','sales_orders','shipments','sales_invoices','purchase_requests','purchase_orders','receipts','purchase_invoices'] as $table) {
            Schema::table($table, function (Blueprint $t) { $t->foreignId('location_id')->nullable()->constrained('locations'); $t->foreignId('bin_id')->nullable()->constrained('location_bins'); });
        }
        foreach (['sales_request_lines','sales_order_lines','shipment_lines','sales_invoice_lines','purchase_request_lines','purchase_order_lines','receipt_lines','purchase_invoice_lines'] as $table) {
            Schema::table($table, function (Blueprint $t) { $t->foreignId('location_id')->nullable()->constrained('locations'); $t->foreignId('bin_id')->nullable()->constrained('location_bins'); });
        }
        foreach (['posted_shipments','posted_receipts'] as $table) { Schema::table($table, function(Blueprint $t){ $t->foreignId('location_id')->nullable()->constrained('locations'); $t->foreignId('bin_id')->nullable()->constrained('location_bins'); }); }
        foreach (['posted_shipment_lines','posted_receipt_lines'] as $table) { Schema::table($table, function(Blueprint $t){ $t->foreignId('location_id')->nullable()->constrained('locations'); $t->foreignId('bin_id')->nullable()->constrained('location_bins'); }); }
        Schema::table('item_ledgers', function (Blueprint $t) {
            $t->foreignId('location_id')->nullable()->constrained('locations'); $t->foreignId('bin_id')->nullable()->constrained('location_bins'); $t->unsignedBigInteger('goods_transfer_id')->nullable()->index(); $t->string('movement_type',50)->nullable();
        });
        Schema::table('gl_batches', function (Blueprint $t) { $t->unsignedBigInteger('goods_transfer_id')->nullable()->index(); });

        Schema::create('goods_transfer_requests', function (Blueprint $t) {
            $t->id(); $t->string('document_no',120)->unique(); $t->date('document_date'); $t->string('status',20)->default('OPEN');
            $t->foreignId('source_location_id')->constrained('locations'); $t->foreignId('source_bin_id')->nullable()->constrained('location_bins');
            $t->foreignId('destination_location_id')->constrained('locations'); $t->foreignId('destination_bin_id')->nullable()->constrained('location_bins');
            $t->text('notes')->nullable(); $t->foreignId('created_by')->constrained('users'); $t->foreignId('released_by')->nullable()->constrained('users'); $t->timestamp('released_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users'); $t->timestamp('approved_at')->nullable(); $t->foreignId('rejected_by')->nullable()->constrained('users'); $t->timestamp('rejected_at')->nullable(); $t->text('rejection_reason')->nullable(); $t->timestamps();
        });
        Schema::create('goods_transfer_request_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_transfer_request_id')->constrained()->cascadeOnDelete(); $t->foreignId('item_id')->constrained('items'); $t->decimal('quantity',19,4); $t->text('description')->nullable(); $t->timestamps();
        });
        Schema::create('goods_transfers', function (Blueprint $t) {
            $t->id(); $t->string('document_no',120)->unique(); $t->foreignId('goods_transfer_request_id')->constrained('goods_transfer_requests'); $t->date('document_date'); $t->string('status',20)->default('OPEN');
            $t->foreignId('source_location_id')->constrained('locations'); $t->foreignId('source_bin_id')->nullable()->constrained('location_bins'); $t->foreignId('destination_location_id')->constrained('locations'); $t->foreignId('destination_bin_id')->nullable()->constrained('location_bins');
            $t->text('notes')->nullable(); $t->foreignId('created_by')->constrained('users'); $t->foreignId('released_by')->nullable()->constrained('users'); $t->timestamp('released_at')->nullable(); $t->foreignId('shipped_by')->nullable()->constrained('users'); $t->timestamp('shipped_at')->nullable(); $t->foreignId('received_by')->nullable()->constrained('users'); $t->timestamp('received_at')->nullable(); $t->foreignId('undone_by')->nullable()->constrained('users'); $t->timestamp('undone_at')->nullable(); $t->timestamps();
        });
        Schema::create('goods_transfer_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_transfer_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_transfer_request_line_id')->constrained('goods_transfer_request_lines'); $t->foreignId('item_id')->constrained('items'); $t->decimal('quantity',19,4); $t->decimal('shipped_quantity',19,4)->default(0); $t->decimal('received_quantity',19,4)->default(0); $t->decimal('unit_cost',19,4)->default(0); $t->timestamps();
        });
        Schema::create('goods_transfer_receipts', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_transfer_id')->constrained()->cascadeOnDelete(); $t->string('receipt_no',120)->unique(); $t->dateTime('received_at'); $t->foreignId('received_by')->constrained('users'); $t->text('notes')->nullable(); $t->timestamps();
        });
        Schema::create('goods_transfer_receipt_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_transfer_receipt_id')->constrained()->cascadeOnDelete(); $t->foreignId('goods_transfer_line_id')->constrained('goods_transfer_lines'); $t->decimal('quantity',19,4); $t->timestamps();
        });
    }
    public function down(): void {
        foreach (['goods_transfer_receipt_lines','goods_transfer_receipts','goods_transfer_lines','goods_transfers','goods_transfer_request_lines','goods_transfer_requests'] as $table) Schema::dropIfExists($table);
        Schema::table('item_ledgers', function(Blueprint $t){ foreach(['location_id','bin_id','goods_transfer_id','movement_type'] as $c) if(Schema::hasColumn('item_ledgers',$c)) $t->dropColumn($c); });
        foreach (['sales_requests','sales_orders','shipments','sales_invoices','purchase_requests','purchase_orders','receipts','purchase_invoices','sales_request_lines','sales_order_lines','shipment_lines','sales_invoice_lines','purchase_request_lines','purchase_order_lines','receipt_lines','purchase_invoice_lines'] as $table) {
            Schema::table($table, function(Blueprint $t) use($table){ foreach(['location_id','bin_id'] as $c) if(Schema::hasColumn($table,$c)) $t->dropColumn($c); });
        }
        Schema::dropIfExists('location_bins'); Schema::dropIfExists('locations'); Schema::dropIfExists('user_view_preferences'); Schema::dropIfExists('data_views');
    }
};
