<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('price_update_lines', function (Blueprint $t) {
            $t->string('approval_status',20)->default('PENDING')->after('validation_status')->index();
            $t->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users');
            $t->timestamp('approved_at')->nullable()->after('approved_by');
            $t->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users');
            $t->timestamp('rejected_at')->nullable()->after('rejected_by');
            $t->text('rejection_reason')->nullable()->after('rejected_at');
            $t->index(['price_update_batch_id','item_id','approval_status'],'price_update_lines_item_approval_idx');
        });

        $approvedBatchIds = DB::table('price_update_batches')->where('status','APPROVED')->pluck('id');
        if ($approvedBatchIds->isNotEmpty()) {
            DB::table('price_update_lines')->whereIn('price_update_batch_id',$approvedBatchIds)->update(['approval_status'=>'APPROVED']);
        }
        $rejectedBatchIds = DB::table('price_update_batches')->where('status','REJECTED')->pluck('id');
        if ($rejectedBatchIds->isNotEmpty()) {
            DB::table('price_update_lines')->whereIn('price_update_batch_id',$rejectedBatchIds)->update(['approval_status'=>'REJECTED']);
        }
    }

    public function down(): void {
        Schema::table('price_update_lines', function (Blueprint $t) {
            $t->dropIndex('price_update_lines_item_approval_idx');
            $t->dropConstrainedForeignId('approved_by');
            $t->dropConstrainedForeignId('rejected_by');
            $t->dropColumn(['approval_status','approved_at','rejected_at','rejection_reason']);
        });
    }
};
