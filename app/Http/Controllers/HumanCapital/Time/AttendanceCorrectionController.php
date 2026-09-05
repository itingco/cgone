<?php
namespace App\Http\Controllers\HumanCapital\Time;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\Time\SaveAttendanceCorrectionRequest;
use App\Models\HumanCapital\Time\{AttendanceCorrection,AttendanceRecord};
use App\Services\Audit\ActivityLogService;
use App\Services\HumanCapital\Time\AttendanceCorrectionService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class AttendanceCorrectionController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz,private readonly ActivityLogService $audit,private readonly AttendanceCorrectionService $workflow){}
    public function index():View{$this->gate('view');$rows=AttendanceCorrection::with('attendance.employee')->orderByDesc('id')->paginate(30);return view('human-capital.time.corrections.index',compact('rows'));}
    public function create():View{$this->gate('edit');$attendance=AttendanceRecord::with('employee')->findOrFail((int)request('attendance_id'));return view('human-capital.time.corrections.form',compact('attendance'));}
    public function store(SaveAttendanceCorrectionRequest $request):RedirectResponse{$this->gate('edit');$d=$request->validated();unset($d['attachment']);if($request->hasFile('attachment'))$d['attachment_path']=$request->file('attachment')->store('hr/attendance-corrections');$d['status']='DRAFT';$d['requested_by']=auth()->id();$row=AttendanceCorrection::create($d);$this->audit->record('hr.attendance-corrections','create',$row,[],$row->toArray(),[]);return redirect()->route('hr.attendance-corrections.index')->with('success','Correction draft created.');}
    public function submit(AttendanceCorrection $correction):RedirectResponse{$this->gate('edit');$before=$correction->toArray();$row=$this->workflow->submit($correction,auth()->id());$this->audit->record('hr.attendance-corrections','submit',$row,$before,$row->toArray(),[]);return back()->with('success','Correction submitted.');}
    public function approve(AttendanceCorrection $correction):RedirectResponse{$this->gate('approve');$before=$correction->toArray();$row=$this->workflow->approve($correction,auth()->id());$this->audit->record('hr.attendance-corrections','approve',$row,$before,$row->toArray(),[]);return back()->with('success','Correction approved. Raw attendance remains unchanged.');}
    public function reject(AttendanceCorrection $correction):RedirectResponse{$this->gate('approve');$before=$correction->toArray();$row=$this->workflow->reject($correction,auth()->id(),request('reason'));$this->audit->record('hr.attendance-corrections','reject',$row,$before,$row->toArray(),[]);return back()->with('success','Correction rejected.');}
    private function gate(string $action):void{abort_unless($this->authz->allows(auth()->user(),'hr.attendance-corrections',$action),403);}
}
