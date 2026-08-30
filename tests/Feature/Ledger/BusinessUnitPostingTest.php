<?php

namespace Tests\Feature\Ledger;

use App\Models\{BusinessUnit, ChartOfAccount, User};
use App\Services\Ledger\GeneralLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessUnitPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_gl_batch_uses_default_business_unit_when_header_does_not_supply_one(): void
    {
        $user = User::factory()->create();
        $cash = ChartOfAccount::create([
            'code' => '1000', 'name' => 'Cash', 'account_type' => 'ASSET',
            'normal_balance' => 'DEBIT', 'allow_posting' => true, 'is_active' => true,
        ]);
        $capital = ChartOfAccount::create([
            'code' => '3000', 'name' => 'Capital', 'account_type' => 'EQUITY',
            'normal_balance' => 'CREDIT', 'allow_posting' => true, 'is_active' => true,
        ]);

        $defaultBu = BusinessUnit::query()->where('is_default', true)->firstOrFail();

        $batch = app(GeneralLedgerService::class)->postBatch([
            'document_number' => 'JV/BU/001',
            'posting_at' => now(),
            'source_module' => 'TEST',
            'document_type' => 'JV',
            'posted_by' => $user->id,
        ], [
            ['account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['account_id' => $capital->id, 'debit' => 0, 'credit' => 100],
        ]);

        $this->assertSame($defaultBu->id, $batch->business_unit_id);
        $this->assertDatabaseHas('gl_batches', [
            'id' => $batch->id,
            'business_unit_id' => $defaultBu->id,
        ]);
    }
}
