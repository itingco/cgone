<?php
namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemAlias;
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemAliasController extends Controller
{
    private function authorizeEdit(): void
    {
        $allowed = app(MenuAuthorizationService::class)->allows(auth()->user(), 'master.items', 'edit');
        abort_unless($allowed, 403);
    }

    private function validated(Request $request, ?ItemAlias $alias = null): array
    {
        return $request->validate([
            'alias_code' => ['required','string','max:150',Rule::unique('item_aliases','alias_code')->ignore($alias?->id)],
            'alias_type' => ['required',Rule::in(['BARCODE','SUPPLIER','CUSTOMER','INTERNAL','OTHER'])],
            'uom_id' => ['nullable','exists:uoms,id'],
            'description' => ['nullable','string','max:255'],
            'is_active' => ['required','boolean'],
        ]);
    }

    public function store(Request $request, Item $item, ActivityLogService $audit)
    {
        $this->authorizeEdit();
        $data = $this->validated($request);
        $alias = DB::transaction(function () use ($item, $data, $audit) {
            $alias = $item->aliases()->create($data);
            $audit->record('master.items','alias_create',$item,[], $alias->toArray(), ['alias_id'=>$alias->id]);
            return $alias;
        });
        return redirect()->route('master.items.show',$item)->with('success','Alias item berhasil ditambahkan.');
    }

    public function update(Request $request, Item $item, ItemAlias $alias, ActivityLogService $audit)
    {
        $this->authorizeEdit();
        abort_unless((int)$alias->item_id === (int)$item->id, 404);
        $data = $this->validated($request, $alias);
        $before = $alias->toArray();
        DB::transaction(function () use ($item,$alias,$data,$before,$audit) {
            $alias->update($data);
            $audit->record('master.items','alias_update',$item,$before,$alias->fresh()->toArray(),['alias_id'=>$alias->id]);
        });
        return redirect()->route('master.items.show',$item)->with('success','Alias item berhasil diperbarui.');
    }

    public function destroy(Item $item, ItemAlias $alias, ActivityLogService $audit)
    {
        $this->authorizeEdit();
        abort_unless((int)$alias->item_id === (int)$item->id, 404);
        $before = $alias->toArray();
        $alias->delete();
        $audit->record('master.items','alias_delete',$item,$before,[],['alias_id'=>$alias->id]);
        return redirect()->route('master.items.show',$item)->with('success','Alias item berhasil dihapus.');
    }
}
