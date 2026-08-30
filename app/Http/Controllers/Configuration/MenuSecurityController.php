<?php
namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\{Menu,Permission,Role,RoleDataScope,RoleMenuPermission};
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuSecurityController extends Controller
{
    public function __construct(){ $this->middleware('menu.permission:config.menu-security,view')->only(['index','edit']);$this->middleware('menu.permission:config.menu-security,edit')->only('update'); }

    public function index(){ return view('configuration.menu-security.index',['roles'=>Role::orderBy('code')->get()]); }

    public function edit(Role $role)
    {
        $menus=Menu::with('parent')->where('is_active',true)->orderBy('sort_order')->orderBy('label')->get();
        $permissions=Permission::where('code','<>','reverse')->orderBy('id')->get();
        $grants=RoleMenuPermission::where('role_id',$role->id)->get()->mapWithKeys(fn($g)=>[$g->menu_id.':'.$g->permission_id=>true]);
        $menuGroups=$menus->filter(fn($m)=>!str_starts_with($m->code,'section.'))->groupBy(fn($m)=>$m->parent?->label ?: $this->categoryFor($m->code));
        $scopeDefinitions=config('role-data-scopes',[]);
        $scopeMenus=Menu::whereIn('code',array_keys($scopeDefinitions))->where('is_active',true)->orderBy('label')->get()->keyBy('code');
        $scopes=RoleDataScope::where('role_id',$role->id)->with('menu')->orderBy('sort_order')->orderBy('id')->get();
        return view('configuration.menu-security.edit',compact('role','permissions','grants','menuGroups','scopeDefinitions','scopeMenus','scopes'));
    }

    public function update(Request $r, Role $role, ActivityLogService $audit)
    {
        $data=$r->validate(['grants'=>['nullable','array'],'grants.*'=>['string'],'scopes'=>['nullable','array'],'scopes.*.module_field'=>['nullable','string'],'scopes.*.operator'=>['nullable','string'],'scopes.*.value'=>['nullable','string']]);
        $before=['grants'=>RoleMenuPermission::where('role_id',$role->id)->get()->toArray(),'scopes'=>RoleDataScope::where('role_id',$role->id)->get()->toArray()];
        $pairs=[];
        foreach($data['grants']??[] as $value){[$menuId,$permissionId]=array_map('intval',explode(':',$value,2));if(Menu::whereKey($menuId)->exists()&&Permission::whereKey($permissionId)->exists())$pairs[]=['role_id'=>$role->id,'menu_id'=>$menuId,'permission_id'=>$permissionId];}
        $scopes=$this->validatedScopes($data['scopes']??[], $role);

        DB::transaction(function() use($role,$pairs,$scopes){RoleMenuPermission::where('role_id',$role->id)->delete();if($pairs)RoleMenuPermission::insert($pairs);RoleDataScope::where('role_id',$role->id)->delete();if($scopes)RoleDataScope::insert($scopes);});
        $after=['grants'=>RoleMenuPermission::where('role_id',$role->id)->get()->toArray(),'scopes'=>RoleDataScope::where('role_id',$role->id)->get()->toArray()];
        $audit->record('config.menu-security','update',$role,$before,$after);
        return redirect()->route('config.menu-security.edit',$role)->with('success','Permissions and data filters updated.');
    }

    private function validatedScopes(array $rows, Role $role): array
    {
        $definitions=config('role-data-scopes',[]);$out=[];$sort=0;
        foreach($rows as $row){$moduleField=trim((string)($row['module_field']??''));if($moduleField==='')continue;[$menuCode,$field]=array_pad(explode('|',$moduleField,2),2,null);$definition=$definitions[$menuCode][$field]??null;$operator=strtoupper(trim((string)($row['operator']??'')));if(!$definition||!in_array($operator,$definition['operators']??[],true))throw ValidationException::withMessages(['scopes'=>'Invalid data filter field/operator.']);$menuId=Menu::where('code',$menuCode)->value('id');if(!$menuId)throw ValidationException::withMessages(['scopes'=>'Selected module is not available.']);$out[]=['role_id'=>$role->id,'menu_id'=>$menuId,'field'=>$field,'operator'=>$operator,'value'=>in_array($operator,['IS NULL','IS NOT NULL'],true)?null:($row['value']??null),'sort_order'=>$sort++,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()];}
        return $out;
    }

    private function categoryFor(string $code): string
    {
        return match(true){str_starts_with($code,'sales.')=>'Sales / AR',str_starts_with($code,'purchase.')=>'Purchase / AP',str_starts_with($code,'inventory.')=>'Inventory',str_starts_with($code,'pricing.')=>'Pricing',str_starts_with($code,'finance.')||str_starts_with($code,'ledger.')=>'Finance',str_starts_with($code,'master.')=>'Master Data',str_starts_with($code,'config.')=>'Configuration',str_starts_with($code,'audit.')=>'Audit',default=>'Other'};
    }
}
