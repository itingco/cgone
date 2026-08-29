<?php
namespace App\Http\Controllers\MasterData;
use App\Http\Controllers\Controller; use App\Models\{Location,LocationBin}; use Illuminate\Http\Request; use Illuminate\Validation\Rule;
class LocationBinController extends Controller {
 public function store(Request $r,Location $location){abort_unless(app(\App\Services\Security\MenuAuthorizationService::class)->allows($r->user(),'inventory.locations','edit'),403);$d=$r->validate(['code'=>['required','string','max:50',Rule::unique('location_bins','code')->where('location_id',$location->id)],'name'=>'required|string|max:255','description'=>'nullable|string','additional_discount_pct'=>'required|numeric|min:0|max:100','is_active'=>'required|boolean']);$location->bins()->create($d);return back()->with('success','Bin created.');}
 public function update(Request $r,Location $location,LocationBin $bin){abort_unless((int)$bin->location_id===(int)$location->id,404);$d=$r->validate(['name'=>'required|string|max:255','description'=>'nullable|string','additional_discount_pct'=>'required|numeric|min:0|max:100','is_active'=>'required|boolean']);$bin->update($d);return back()->with('success','Bin updated.');}
 public function destroy(Location $location,LocationBin $bin){abort_unless((int)$bin->location_id===(int)$location->id,404);$bin->delete();return back()->with('success','Bin deleted.');}
}
