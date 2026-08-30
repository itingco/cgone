<?php
namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemUom;
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemUomController extends Controller
{
    private function authorizeEdit(): void
    {
        abort_unless(app(MenuAuthorizationService::class)->allows(auth()->user(), 'master.items', 'edit'), 403);
    }

    private function validated(Request $request, Item $item, ?ItemUom $row = null): array
    {
        return $request->validate([
            'level' => ['required','integer','between:2,4', Rule::unique('item_uoms','level')->where(fn($q)=>$q->where('item_id',$item->id))->ignore($row?->id)],
            'uom_id' => ['required','exists:uoms,id'],
            'conversion_qty' => ['required','numeric','gt:0'],
            'is_sales_uom' => ['required','boolean'],
            'is_purchase_uom' => ['required','boolean'],
        ]);
    }

    public function store(Request $request, Item $item, ActivityLogService $audit)
    {
        $this->authorizeEdit();
        $data = $this->validated($request, $item);
        DB::transaction(function () use ($item,$data,$audit) {
            $row = $item->uoms()->create($data);
            $audit->record('master.items','uom_create',$item,[],$row->toArray(),['item_uom_id'=>$row->id]);
        });
        return redirect()->route('master.items.show',$item)->with('success','UOM level berhasil ditambahkan.');
    }

    public function update(Request $request, Item $item, ItemUom $uomLevel, ActivityLogService $audit)
    {
        $this->authorizeEdit();
        abort_unless((int)$uomLevel->item_id === (int)$item->id,404);
        $data = $this->validated($request,$item,$uomLevel);
        $before = $uomLevel->toArray();
        DB::transaction(function () use ($item,$uomLevel,$data,$audit,$before) {
            $uomLevel->update($data);
            $audit->record('master.items','uom_update',$item,$before,$uomLevel->fresh()->toArray(),['item_uom_id'=>$uomLevel->id]);
        });
        return redirect()->route('master.items.show',$item)->with('success','UOM level berhasil diperbarui.');
    }

    public function destroy(Item $item, ItemUom $uomLevel, ActivityLogService $audit)
    {
        $this->authorizeEdit();
        abort_unless((int)$uomLevel->item_id === (int)$item->id,404);
        $before = $uomLevel->toArray();
        DB::transaction(function () use ($item,$uomLevel,$audit,$before) {
            $id=$uomLevel->id;
            $uomLevel->delete();
            $audit->record('master.items','uom_delete',$item,$before,[],['item_uom_id'=>$id]);
        });
        return redirect()->route('master.items.show',$item)->with('success','UOM level berhasil dihapus.');
    }
}
