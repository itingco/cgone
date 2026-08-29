<?php
namespace Tests\Feature\Ledger; use App\Models\ItemLedger; use DomainException; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class ImmutableLedgerTest extends TestCase { use RefreshDatabase; public function test_posted_item_ledger_rejects_update(): void {$ledger=ItemLedger::factory()->create();$this->expectException(DomainException::class);$ledger->update(['description'=>'changed']);} public function test_posted_item_ledger_rejects_delete(): void {$ledger=ItemLedger::factory()->create();$this->expectException(DomainException::class);$ledger->delete();} }
