<?php

namespace Tests\Feature\Audit;

use App\Models\Item;
use App\Models\Uom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\PermissionHelper;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;
    use PermissionHelper;

    public function test_master_update_records_before_and_after_values(): void
    {
        $uom = Uom::factory()->create();

        $item = Item::factory()->create([
            'base_uom_id' => $uom->id,
            'name' => 'Old Name',
        ]);

        $user = $this->userWithPermission(
            'master.items',
            'edit'
        );

        $this->actingAs($user)
            ->put('/master/items/'.$item->id, [
                'code' => $item->code,
                'name' => 'New Name',
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

        $log = \App\Models\ActivityLog::where(
            'module',
            'master.items'
        )
            ->where(
                'action',
                'update'
            )
            ->latest('id')
            ->firstOrFail();

        $this->assertStringContainsString(
            'Old Name',
            (string) $log->before_data
        );

        $this->assertStringContainsString(
            'New Name',
            (string) $log->after_data
        );
    }
}