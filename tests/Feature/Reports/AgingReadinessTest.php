<?php

namespace Tests\Feature\Reports;

use App\Models\{Customer,User,Vendor};
use App\Services\Reports\ReportDataRequirementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgingReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unapplied_customer_credit_blocks_aging(): void
    {
        $user=User::factory()->create();
        $customer=Customer::create(['code'=>'C-AGING','name'=>'Aging Customer','is_active'=>true]);

        DB::table('customer_ledgers')->insert([
            'posting_at'=>'2026-08-15 10:00:00','source_module'=>'payment','document_type'=>'PAYMENT','document_number'=>'PAY-1',
            'customer_id'=>$customer->id,'debit'=>0,'credit'=>100000,'description'=>'Unapplied payment','posted_by'=>$user->id,
            'status'=>'POSTED','created_at'=>now(),'updated_at'=>now(),
        ]);

        $this->assertFalse(app(ReportDataRequirementService::class)->customerAgingReady(['as_of'=>'2026-08-30']));
    }

    public function test_unapplied_vendor_debit_blocks_aging(): void
    {
        $user=User::factory()->create();
        $vendor=Vendor::create(['code'=>'V-AGING','name'=>'Aging Vendor','is_active'=>true]);

        DB::table('vendor_ledgers')->insert([
            'posting_at'=>'2026-08-15 10:00:00','source_module'=>'payment','document_type'=>'PAYMENT','document_number'=>'VPAY-1',
            'vendor_id'=>$vendor->id,'debit'=>100000,'credit'=>0,'description'=>'Unapplied payment','posted_by'=>$user->id,
            'status'=>'POSTED','created_at'=>now(),'updated_at'=>now(),
        ]);

        $this->assertFalse(app(ReportDataRequirementService::class)->vendorAgingReady(['as_of'=>'2026-08-30']));
    }
}
