<?php

namespace Tests\Feature\Reports;

use App\Models\Reports\ReportDefinition;
use Database\Seeders\{ReportingSeeder,SecuritySeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class R3ReportingSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_r3_report_definitions_and_transfer_business_unit_are_available(): void
    {
        $this->assertTrue(Schema::hasColumn('goods_transfer_requests','business_unit_id'));
        $this->assertTrue(Schema::hasColumn('goods_transfers','business_unit_id'));

        $this->seed(SecuritySeeder::class);
        $this->seed(ReportingSeeder::class);

        foreach ([
            'STOCK_CARD','NEGATIVE_STOCK','REORDER_REPORT','SLOW_MOVING','DEAD_STOCK','FAST_MOVING',
            'TRANSFER_HISTORY','COGS_DETAIL','INVENTORY_AGING',
            'GENERAL_LEDGER_DETAIL','ACCOUNT_MOVEMENT','PROFIT_LOSS_BY_BU','CASH_FLOW',
            'AR_GL_RECONCILIATION','AP_GL_RECONCILIATION','INVENTORY_GL_RECONCILIATION',
        ] as $code) {
            $this->assertTrue(
                ReportDefinition::query()->where('code',$code)->where('is_active',true)->exists(),
                $code.' missing'
            );
        }

        $this->assertSame(46, ReportDefinition::query()->where('is_system',true)->count());
    }
}
