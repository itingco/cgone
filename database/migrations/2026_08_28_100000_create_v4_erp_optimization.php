<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->index();
        });

        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('widget_key', 80);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->unsignedTinyInteger('width')->default(3);
            $table->boolean('is_enabled')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['user_id','widget_key']);
            $table->index(['user_id','is_enabled','sort_order']);
        });

        // Existing customer/vendor ledgers already have entity+posting indexes.
        // These V4 indexes target master-history drilldowns and document-type filtering.
        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->index(['item_id','posting_at'], 'v4_item_ledgers_item_posting_idx');
            $table->index(['item_id','source_module','posting_at'], 'v4_item_ledgers_source_idx');
        });
        Schema::table('customer_ledgers', function (Blueprint $table) {
            $table->index(['customer_id','document_type','posting_at'], 'v4_customer_ledgers_doc_idx');
        });
        Schema::table('vendor_ledgers', function (Blueprint $table) {
            $table->index(['vendor_id','document_type','posting_at'], 'v4_vendor_ledgers_doc_idx');
        });
    }

    public function down(): void {
        Schema::table('vendor_ledgers', fn (Blueprint $table) => $table->dropIndex('v4_vendor_ledgers_doc_idx'));
        Schema::table('customer_ledgers', fn (Blueprint $table) => $table->dropIndex('v4_customer_ledgers_doc_idx'));
        Schema::table('item_ledgers', function (Blueprint $table) {
            $table->dropIndex('v4_item_ledgers_item_posting_idx');
            $table->dropIndex('v4_item_ledgers_source_idx');
        });
        Schema::dropIfExists('user_dashboard_preferences');
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['department']);
            $table->dropColumn('department');
        });
    }
};
