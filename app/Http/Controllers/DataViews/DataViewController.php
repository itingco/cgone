<?php
namespace App\Http\Controllers\DataViews;
use App\Http\Controllers\Controller;
use App\Models\DataViews\{DataView,UserViewPreference};
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\Request;

class DataViewController extends Controller {
    public function store(Request $r){$d=$r->validate(['module_key'=>'required|string|max:120','name'=>'required|string|max:120','scope'=>'required|in:personal,company','columns'=>'nullable|array','filters'=>'nullable|array','sort'=>'nullable|array','filter_mode'=>'required|in:AND,OR','page_size'=>'required|in:25,50,100,200','make_default'=>'nullable|boolean']);$this->companyGate($r,$d['scope']);$view=DataView::create(['module_key'=>$d['module_key'],'name'=>$d['name'],'scope'=>$d['scope'],'user_id'=>$d['scope']==='personal'?$r->user()->id:null,'columns_json'=>$d['columns']??[],'filters_json'=>$d['filters']??[],'sort_json'=>$d['sort']??[],'filter_mode'=>$d['filter_mode'],'page_size'=>$d['page_size'],'created_by'=>$r->user()->id,'updated_by'=>$r->user()->id]);if($r->boolean('make_default'))$this->setDefault($r,$view);return back()->with('success','View saved.');}
    public function update(Request $r,DataView $view){$this->ownershipGate($r,$view);$d=$r->validate(['name'=>'required|string|max:120','columns'=>'nullable|array','filters'=>'nullable|array','sort'=>'nullable|array','filter_mode'=>'required|in:AND,OR','page_size'=>'required|in:25,50,100,200']);$view->update(['name'=>$d['name'],'columns_json'=>$d['columns']??[],'filters_json'=>$d['filters']??[],'sort_json'=>$d['sort']??[],'filter_mode'=>$d['filter_mode'],'page_size'=>$d['page_size'],'updated_by'=>$r->user()->id]);return back()->with('success','View updated.');}
    public function destroy(Request $r,DataView $view){$this->ownershipGate($r,$view);abort_if($view->is_system,422,'System view cannot be deleted.');$view->delete();return back()->with('success','View deleted.');}
    public function makeDefault(Request $r,DataView $view){abort_unless($view->scope==='company'||(int)$view->user_id===(int)$r->user()->id,403);$this->setDefault($r,$view);return back()->with('success','My default view updated.');}
    private function setDefault(Request $r,DataView $view): void {UserViewPreference::updateOrCreate(['user_id'=>$r->user()->id,'module_key'=>$view->module_key],['data_view_id'=>$view->id]);}
    private function companyGate(Request $r,string $scope): void {if($scope!=='company')return;abort_unless(app(MenuAuthorizationService::class)->allows($r->user(),'config.data-views','edit'),403);}
    private function ownershipGate(Request $r,DataView $view): void {if($view->scope==='personal'){abort_unless((int)$view->user_id===(int)$r->user()->id,403);return;}$this->companyGate($r,'company');}
}
