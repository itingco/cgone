<?php
namespace App\Http\Controllers\HumanCapital\Time;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\Time\SaveShiftRequest;
use App\Models\HumanCapital\Time\Shift;
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ShiftController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz, private readonly ActivityLogService $audit) {}
    public function index():View{$this->gate('view');$rows=Shift::query()->orderByDesc('is_active')->orderBy('code')->paginate(30);return view('human-capital.time.shifts.index',compact('rows'));}
    public function create():View{$this->gate('create');return view('human-capital.time.shifts.form',['shift'=>new Shift(['is_active'=>true,'break_minutes'=>60,'standard_work_minutes'=>480,'overtime_eligible'=>true])]);}
    public function store(SaveShiftRequest $request):RedirectResponse{$this->gate('create');$data=$this->data($request);if(Shift::where('code',$data['code'])->exists())throw ValidationException::withMessages(['code'=>'Shift code already exists.']);$row=Shift::create($data);$this->audit->record('hr.shifts','create',$row,[],$row->toArray(),['code'=>$row->code]);return redirect()->route('hr.shifts.index')->with('success','Shift created.');}
    public function edit(Shift $shift):View{$this->gate('edit');return view('human-capital.time.shifts.form',compact('shift'));}
    public function update(SaveShiftRequest $request,Shift $shift):RedirectResponse{$this->gate('edit');$data=$this->data($request);if(Shift::where('code',$data['code'])->where('id','!=',$shift->id)->exists())throw ValidationException::withMessages(['code'=>'Shift code already exists.']);$before=$shift->toArray();$shift->update($data);$this->audit->record('hr.shifts','update',$shift,$before,$shift->fresh()->toArray(),['code'=>$shift->code]);return back()->with('success','Shift updated.');}
    private function data(SaveShiftRequest $r):array{$d=$r->validated();$d['overtime_eligible']=$r->boolean('overtime_eligible');$d['cross_day']=$r->boolean('cross_day');$d['is_active']=$r->boolean('is_active');return $d;}
    private function gate(string $action):void{abort_unless($this->authz->allows(auth()->user(),'hr.shifts',$action),403);}
}
