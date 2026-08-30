<?php
namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\{Department,Role,User};
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(){ $this->middleware('menu.permission:config.users,view')->only('index');$this->middleware('menu.permission:config.users,create')->only(['create','store']);$this->middleware('menu.permission:config.users,edit')->only(['edit','update','status']); }

    public function index(){ return view('configuration.users.index',['rows'=>User::with(['roles','department'])->orderBy('name')->paginate(30)]); }
    public function create(){ return view('configuration.users.form',$this->formData(new User,false)); }

    public function store(Request $r, ActivityLogService $audit)
    {
        $data=$this->validated($r);
        return DB::transaction(function() use($data,$audit){$roles=$data['role_ids']??[];unset($data['role_ids']);$u=User::create($data);$u->roles()->sync($roles);$audit->record('config.users','create',$u,[],$u->load(['roles','department'])->toArray());return redirect()->route('config.users.index')->with('success','User created.');});
    }

    public function edit(User $user){ return view('configuration.users.form',$this->formData($user->load('roles'),true)); }

    public function update(Request $r, User $user, ActivityLogService $audit)
    {
        $data=$this->validated($r,$user);
        return DB::transaction(function() use($data,$user,$audit){$before=$user->load(['roles','department'])->toArray();$roles=$data['role_ids']??[];unset($data['role_ids']);if(empty($data['password']))unset($data['password']);$user->update($data);$user->roles()->sync($roles);$audit->record('config.users','update',$user,$before,$user->fresh()->load(['roles','department'])->toArray());return redirect()->route('config.users.index')->with('success','User updated.');});
    }

    public function status(Request $r, User $user, ActivityLogService $audit){$d=$r->validate(['is_active'=>['required','boolean']]);$before=$user->toArray();$user->update(['is_active'=>(bool)$d['is_active']]);$audit->record('config.users',$user->is_active?'activate':'deactivate',$user,$before,$user->fresh()->toArray());return back()->with('success','User status updated.');}

    private function validated(Request $r, ?User $user=null): array
    {
        return $r->validate([
            'department_id'=>['nullable','exists:departments,id'],
            'name'=>['required','string','max:255'],
            'email'=>['required','email',Rule::unique('users','email')->ignore($user?->id)],
            'password'=>[$user?'nullable':'required','string','min:8'],
            'role_ids'=>['nullable','array'],
            'role_ids.*'=>['exists:roles,id'],
            'is_active'=>['required','boolean'],
        ]);
    }

    private function formData(User $record, bool $isEdit): array
    {
        return ['record'=>$record,'roles'=>Role::where('is_active',true)->orderBy('name')->get(),'departments'=>Department::where('is_active',true)->orderBy('name')->get(),'isEdit'=>$isEdit];
    }
}
