<?php
namespace App\Http\Controllers\HumanCapital\Time;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\Time\{ImportAttendanceRequest,SaveAttendanceRequest};
use App\Models\HumanCapital\Employee;
use App\Models\HumanCapital\Time\AttendanceRecord;
use App\Services\Audit\ActivityLogService;
use App\Services\HumanCapital\Time\AttendanceImportService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AttendanceController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz,private readonly ActivityLogService $audit,private readonly AttendanceImportService $attendance){}
    public function index():View{$this->gate('view');$q=AttendanceRecord::with(['employee','shift']);if(request()->filled('employee_id'))$q->where('employee_id',request('employee_id'));if(request()->filled('date_from'))$q->whereDate('work_date','>=',request('date_from'));if(request()->filled('date_to'))$q->whereDate('work_date','<=',request('date_to'));$rows=$q->orderByDesc('work_date')->orderBy('employee_id')->paginate(40)->withQueryString();$employees=Employee::where('is_active',true)->orderBy('employee_code')->get();return view('human-capital.time.attendance.index',compact('rows','employees'));}
    public function create():View{$this->gate('edit');$employees=Employee::where('is_active',true)->orderBy('employee_code')->get();return view('human-capital.time.attendance.form',compact('employees'));}
    public function store(SaveAttendanceRequest $request):RedirectResponse{$this->gate('edit');$d=$request->validated();$row=$this->attendance->upsert((int)$d['employee_id'],$d['work_date'],$d['check_in']??null,$d['check_out']??null,'MANUAL',$d['source_reference']??'',auth()->id(),$d['notes']??null);$this->audit->record('hr.attendance','update',$row,[],$row->toArray(),['source'=>'MANUAL']);return redirect()->route('hr.attendance.index')->with('success','Attendance saved.');}
    public function import(ImportAttendanceRequest $request):RedirectResponse{$this->gate('edit');$result=$this->attendance->importCsv($request->file('file')->getRealPath(),auth()->id());$message="Attendance import: {$result['processed']} row(s) processed.";if($result['errors'])$message.=' '.count($result['errors']).' error(s): '.implode(' | ',array_slice($result['errors'],0,5));return back()->with($result['errors']?'warning':'success',$message);}
    private function gate(string $action):void{abort_unless($this->authz->allows(auth()->user(),'hr.attendance',$action),403);}
}
