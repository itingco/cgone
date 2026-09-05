<?php
namespace App\Http\Controllers\HumanCapital\Time;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\Time\{SaveOvertimeRequest,SaveOvertimeTypeRequest};
use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Time\{OvertimeRecord,OvertimeType};
use App\Services\Audit\ActivityLogService;
use App\Services\HumanCapital\Time\OvertimeWorkflowService;
use App\Services\Security\MenuAuthorizationService;
use DateTimeImmutable;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class OvertimeController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz,private readonly ActivityLogService $audit,private readonly OvertimeWorkflowService $workflow){}
    public function index():View{$this->gate('view');$rows=OvertimeRecord::with(['employee','overtimeType'])->orderByDesc('work_date')->paginate(30);$types=OvertimeType::orderByDesc('is_active')->orderBy('code')->get();return view('human-capital.time.overtime.index',compact('rows','types'));}
    public function create():View{$this->gate('edit');$employees=Employee::where('is_active',true)->orderBy('employee_code')->get();$types=OvertimeType::where('is_active',true)->orderBy('code')->get();return view('human-capital.time.overtime.form',compact('employees','types'));}
    public function store(SaveOvertimeRequest $request):RedirectResponse{$this->gate('edit');$d=$request->validated();$from=new DateTimeImmutable($d['start_at']);$to=new DateTimeImmutable($d['end_at']);$d['actual_hours']=round(max(0,$to->getTimestamp()-$from->getTimestamp())/3600,2);$d['approved_hours']=0;$d['status']='DRAFT';$d['requested_by']=auth()->id();if(empty($d['rate_percent'])&&!empty($d['overtime_type_id']))$d['rate_percent']=OvertimeType::find($d['overtime_type_id'])?->default_rate_percent;$row=OvertimeRecord::create($d);$this->audit->record('hr.overtime','create',$row,[],$row->toArray(),[]);return redirect()->route('hr.overtime.index')->with('success','Overtime draft created.');}
    public function storeType(SaveOvertimeTypeRequest $request):RedirectResponse{$this->gate('edit');$d=$request->validated();$d['is_active']=$request->boolean('is_active');if(OvertimeType::where('code',$d['code'])->exists())throw ValidationException::withMessages(['code'=>'Overtime type code already exists.']);OvertimeType::create($d);return back()->with('success','Overtime type created.');}
    public function submit(OvertimeRecord $overtime):RedirectResponse{$this->gate('edit');return $this->act($overtime,'submit',fn()=>$this->workflow->submit($overtime,auth()->id()));}
    public function approve(OvertimeRecord $overtime):RedirectResponse{$this->gate('approve');$data=request()->validate(['approved_hours'=>['required','numeric','min:0'],'rate_percent'=>['nullable','numeric','gt:0','max:1000']]);return $this->act($overtime,'approve',fn()=>$this->workflow->approve($overtime,auth()->id(),(float)$data['approved_hours'],isset($data['rate_percent'])?(float)$data['rate_percent']:null));}
    public function reject(OvertimeRecord $overtime):RedirectResponse{$this->gate('approve');return $this->act($overtime,'reject',fn()=>$this->workflow->reject($overtime,auth()->id(),request('reason')));}
    private function act(OvertimeRecord $row,string $action,callable $callback):RedirectResponse{$before=$row->toArray();try{$result=$callback();}catch(DomainException $e){return back()->with('error',$e->getMessage());}$this->audit->record('hr.overtime',$action,$result,$before,$result->toArray(),[]);return back()->with('success','Overtime '.strtolower($result->status).'.');}
    private function gate(string $action):void{abort_unless($this->authz->allows(auth()->user(),'hr.overtime',$action),403);}
}
