<?php

namespace Tests\Feature\MasterData;

use App\Models\Uom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ItemCrudTest extends TestCase
{
    use RefreshDatabase;
    use PermissionHelper;

    public function test_authorized_user_can_create_item(): void
    {
        $uom = Uom::factory()->create();

        $user = $this->userWithPermission(
            'master.items',
            'create'
        );

        $this->actingAs($user)
            ->post('/master/items', [
                'code' => 'ITEM-001',
                'name' => 'Test Item',
                'item_type' => 'INVENTORY',
                'base_uom_id' => $uom->id,

                'min_quantity' => 0,
                'max_quantity' => 0,
                'reorder_level' => 0,
                'min_order' => 0,
                'lead_time_days' => 0,

                'can_be_sold' => true,
                'can_be_purchased' => true,
                'is_discontinued' => false,
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas(
            'items',
            [
                'code' => 'ITEM-001',
            ]
        );

        $this->assertDatabaseHas(
            'activity_logs',
            [
                'module' => 'master.items',
                'action' => 'create',
            ]
        );
    }
}