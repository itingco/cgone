<?php
namespace App\Http\Controllers\HumanCapital\Time;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\Time\{SaveLeaveRequest,SaveLeaveTypeRequest};
use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Time\{LeaveBalance,LeaveRequest,LeaveType};
use App\Services\Audit\ActivityLogService;
use App\Services\HumanCapital\Time\LeaveWorkflowService;
use App\Services\Security\MenuAuthorizationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class LeaveController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz,private readonly ActivityLogService $audit,private readonly LeaveWorkflowService $workflow){}
    public function index():View{$this->gate('view');$rows=LeaveRequest::with(['employee','leaveType'])->orderByDesc('id')->paginate(30);$types=LeaveType::orderByDesc('is_active')->orderBy('code')->get();$employees=Employee::where('is_active',true)->orderBy('employee_code')->get();$balances=LeaveBalance::with([])->orderByDesc('year')->limit(100)->get();return view('human-capital.time.leave.index',compact('rows','types','employees','balances'));}
    public function create():View{$this->gate('edit');$employees=Employee::where('is_active',true)->orderBy('employee_code')->get();$types=LeaveType::where('is_active',true)->orderBy('code')->get();return view('human-capital.time.leave.form',compact('employees','types'));}
    public function store(SaveLeaveRequest $request):RedirectResponse{$this->gate('edit');$d=$request->validated();unset($d['attachment']);if($request->hasFile('attachment'))$d['attachment_path']=$request->file('attachment')->store('hr/leave');$d['status']='DRAFT';$d['requested_by']=auth()->id();$row=LeaveRequest::create($d);try{$this->workflow->assertNoOverlap($row);}catch(\Throwable $e){$row->delete();throw ValidationException::withMessages(['start_date'=>$e->getMessage()]);}$this->audit->record('hr.leave','create',$row,[],$row->toArray(),[]);return redirect()->route('hr.leave.index')->with('success','Leave draft created.');}
    public function storeType(SaveLeaveTypeRequest $request):RedirectResponse{$this->gate('edit');$d=$request->validated();foreach(['is_paid','deduct_balance','requires_attachment','is_active'] as $f)$d[$f]=$request->boolean($f);if(LeaveType::where('code',$d['code'])->exists())throw ValidationException::withMessages(['code'=>'Leave type code already exists.']);LeaveType::create($d);return back()->with('success','Leave type created.');}
    public function saveBalance():RedirectResponse{$this->gate('edit');$d=request()->validate(['employee_id'=>['required','integer','exists:employees,id'],'leave_type_id'=>['required','integer','exists:leave_types,id'],'year'=>['required','integer','min:2000','max:2100'],'opening_days'=>['nullable','numeric'],'accrued_days'=>['nullable','numeric'],'adjustment_days'=>['nullable','numeric']]);LeaveBalance::updateOrCreate(['employee_id'=>$d['employee_id'],'leave_type_id'=>$d['leave_type_id'],'year'=>$d['year']],['opening_days'=>$d['opening_days']??0,'accrued_days'=>$d['accrued_days']??0,'adjustment_days'=>$d['adjustment_days']??0]);return back()->with('success','Leave balance saved.');}
    public function submit(LeaveRequest $leave):RedirectResponse{$this->gate('edit');return $this->act($leave,'submit',fn()=>$this->workflow->submit($leave,auth()->id()));}
    public function approve(LeaveRequest $leave):RedirectResponse{$this->gate('approve');return $this->act($leave,'approve',fn()=>$this->workflow->approve($leave,auth()->id()));}
    public function reject(LeaveRequest $leave):RedirectResponse{$this->gate('approve');return $this->act($leave,'reject',fn()=>$this->workflow->reject($leave,auth()->id(),request('reason')));}
    public function cancel(LeaveRequest $leave):RedirectResponse{$this->gate('edit');return $this->act($leave,'cancel',fn()=>$this->workflow->cancel($leave,auth()->id()));}
    private function act(LeaveRequest $leave,string $action,callable $callback):RedirectResponse{$before=$leave->toArray();try{$row=$callback();}catch(DomainException $e){return back()->with('error',$e->getMessage());}$this->audit->record('hr.leave',$action,$row,$before,$row->toArray(),[]);return back()->with('success','Leave '.strtolower($row->status).'.');}
    private function gate(string $action):void{abort_unless($this->authz->allows(auth()->user(),'hr.leave',$action),403);}
}
