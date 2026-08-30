<?php
namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\{Role,RoleDataScope,RoleMenuPermission};
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('menu.permission:config.roles,view')->only(['index','users']);
        $this->middleware('menu.permission:config.roles,create')->only(['create','store','duplicate']);
        $this->middleware('menu.permission:config.roles,edit')->only(['edit','update']);
    }

    public function index(){ return view('configuration.roles.index',['rows'=>Role::withCount('users')->orderBy('code')->paginate(30)]); }
    public function create(){ return view('configuration.roles.form',['record'=>new Role,'isEdit'=>false]); }

    public function store(Request $r, ActivityLogService $audit)
    {
        $d=$r->validate(['code'=>['required','string','max:100',Rule::unique('roles','code')],'name'=>['required','string','max:255'],'description'=>['nullable','string'],'is_active'=>['required','boolean']]);
        $role=Role::create($d);
        $audit->record('config.roles','create',$role,[],$role->toArray());
        return redirect()->route('config.roles.index')->with('success','Role created.');
    }

    public function edit(Role $role){ return view('configuration.roles.form',['record'=>$role,'isEdit'=>true]); }

    public function update(Request $r, Role $role, ActivityLogService $audit)
    {
        $d=$r->validate(['code'=>['required','string','max:100',Rule::unique('roles','code')->ignore($role->id)],'name'=>['required','string','max:255'],'description'=>['nullable','string'],'is_active'=>['required','boolean']]);
        $before=$role->toArray();
        $role->update($d);
        $audit->record('config.roles','update',$role,$before,$role->fresh()->toArray());
        return redirect()->route('config.roles.index')->with('success','Role updated.');
    }

    public function duplicate(Role $role, ActivityLogService $audit)
    {
        $copy = DB::transaction(function () use ($role, $audit) {
            $baseCode = substr('COPY_'.$role->code, 0, 100);
            $code = $baseCode;
            $i = 2;
            while (Role::where('code', $code)->exists()) {
                $suffix = '_'.$i++;
                $code = substr($baseCode, 0, 100 - strlen($suffix)).$suffix;
            }

            $copy = Role::create([
                'code' => $code,
                'name' => substr('Copy '.$role->name, 0, 255),
                'description' => $role->description,
                'is_active' => $role->is_active,
            ]);

            $role->grants()->get()->each(fn ($g) => RoleMenuPermission::create([
                'role_id'=>$copy->id,'menu_id'=>$g->menu_id,'permission_id'=>$g->permission_id,
            ]));
            $role->dataScopes()->get()->each(fn ($s) => RoleDataScope::create([
                'role_id'=>$copy->id,'menu_id'=>$s->menu_id,'field'=>$s->field,'operator'=>$s->operator,
                'value'=>$s->value,'sort_order'=>$s->sort_order,'is_active'=>$s->is_active,
            ]));

            $audit->record('config.roles','duplicate',$copy,[],$copy->toArray(),['source_role_id'=>$role->id]);
            return $copy;
        });

        return redirect()->route('config.menu-security.edit',$copy)->with('success','Role duplicated. Review permissions and data filters before use.');
    }

    public function users(Role $role, Request $request)
    {
        $users = $role->users()->with('department')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term=(string)$request->input('q');
                $q->where(fn($x)=>$x->where('name','like',"%{$term}%")->orWhere('email','like',"%{$term}%"));
            })
            ->orderBy('name')->paginate(30)->withQueryString();
        return view('configuration.roles.users', compact('role','users'));
    }
}
