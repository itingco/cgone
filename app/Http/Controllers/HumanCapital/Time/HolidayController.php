<?php
namespace App\Http\Controllers\HumanCapital\Time;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\Time\SaveHolidayRequest;
use App\Models\HumanCapital\Time\Holiday;
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class HolidayController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz,private readonly ActivityLogService $audit){}
    public function index():View{$this->gate('view');$rows=Holiday::orderByDesc('holiday_date')->paginate(40);return view('human-capital.time.holidays.index',compact('rows'));}
    public function create():View{$this->gate('edit');return view('human-capital.time.holidays.form',['holiday'=>new Holiday(['is_paid'=>true,'is_active'=>true])]);}
    public function store(SaveHolidayRequest $request):RedirectResponse{$this->gate('edit');$d=$this->data($request);$row=Holiday::create($d);$this->audit->record('hr.holidays','create',$row,[],$row->toArray(),[]);return redirect()->route('hr.holidays.index')->with('success','Holiday created.');}
    public function edit(Holiday $holiday):View{$this->gate('edit');return view('human-capital.time.holidays.form',compact('holiday'));}
    public function update(SaveHolidayRequest $request,Holiday $holiday):RedirectResponse{$this->gate('edit');$before=$holiday->toArray();$holiday->update($this->data($request));$this->audit->record('hr.holidays','update',$holiday,$before,$holiday->fresh()->toArray(),[]);return back()->with('success','Holiday updated.');}
    private function data(SaveHolidayRequest $r):array{$d=$r->validated();$d['is_paid']=$r->boolean('is_paid');$d['is_active']=$r->boolean('is_active');return $d;}
    private function gate(string $action):void{abort_unless($this->authz->allows(auth()->user(),'hr.holidays',$action),403);}
}
