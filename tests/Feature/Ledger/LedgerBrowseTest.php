<?php

namespace Tests\Feature\Ledger;

use App\Models\{Item, ItemLedger, User, Warehouse};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class LedgerBrowseTest extends TestCase
{
    use RefreshDatabase, PermissionHelper;

    public function test_item_ledger_shows_signed_qty_document_type_summary_and_has_no_action_column(): void
    {
        $user = $this->userWithPermission('ledger.items', 'view');
        $item = Item::factory()->create(['code' => 'ITEM-LEDGER']);
        $otherItem = Item::factory()->create(['code' => 'ITEM-OTHER']);
        $warehouse = Warehouse::factory()->create();
        $poster = User::factory()->create();

        ItemLedger::factory()->create([
            'document_type' => 'POSTED_RECEIPT',
            'document_number' => 'PRC-TEST-001',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_in' => 10,
            'qty_out' => 0,
            'amount' => 250000,
            'posted_by' => $poster->id,
        ]);

        ItemLedger::factory()->create([
            'document_type' => 'POSTED_SHIPMENT',
            'document_number' => 'PSH-TEST-001',
            'item_id' => $item->id,
            'warehouse_id' => $warehouse->id,
            'qty_in' => 0,
            'qty_out' => 3,
            'amount' => -75000,
            'posted_by' => $poster->id,
        ]);

        ItemLedger::factory()->create([
            'document_type' => 'POSTED_RECEIPT',
            'document_number' => 'PRC-OTHER-001',
            'item_id' => $otherItem->id,
            'warehouse_id' => $warehouse->id,
            'qty_in' => 100,
            'qty_out' => 0,
            'amount' => 100000,
            'posted_by' => $poster->id,
        ]);

        $response = $this->actingAs($user)->get(route('ledger.items.index', [
            'filters' => [[
                'field' => 'item_code',
                'operator' => 'equals',
                'value' => 'ITEM-LEDGER',
            ]],
        ]));

        $response->assertOk();
        $response->assertSee('Purchase Receipt');
        $response->assertSee('Sales Shipment');
        $response->assertSee('+10,0000', false);
        $response->assertSee('-3,0000', false);
        $response->assertSee('+7,0000', false);
        $response->assertDontSee('Qty In');
        $response->assertDontSee('Qty Out');
        $response->assertDontSee('Action');
        $response->assertDontSee('Reverse');
    }
}
