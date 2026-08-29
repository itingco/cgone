<?php
namespace Tests\Feature\MasterData;
use App\Models\{Item,Uom,User}; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class MasterPermissionTest extends TestCase { use RefreshDatabase; public function test_user_without_edit_permission_cannot_edit_item(): void {$uom=Uom::factory()->create();$item=Item::factory()->create(['base_uom_id'=>$uom->id]);$user=User::factory()->create();$this->actingAs($user)->get('/master/items/'.$item->id.'/edit')->assertForbidden();} }
