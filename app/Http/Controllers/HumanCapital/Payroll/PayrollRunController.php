<?php
namespace App\Http\Controllers\HumanCapital\Payroll;
use App\Http\Controllers\Controller;
use App\Models\HumanCapital\Payroll\{PayrollPeriod,PayrollRuleVersion,StatutoryRuleVersion,TaxRuleVersion,PayrollRun};
use App\Services\HumanCapital\Payroll\PayrollRunService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
final class PayrollRunController extends Controller {
    public function __construct(private readonly MenuAuthorizationService $authz,private readonly PayrollRunService $runs){}
    public function index():View{$this->gate('view','payroll.salary.view');$periods=PayrollPeriod::with(['runs'=>fn($q)=>$q->latest('run_no')])->orderByDesc('period_code')->paginate(24);return view('human-capital.payroll.runs.index',compact('periods'));}
    public function create():View{$this->gate('create','payroll.calculate');$payrollRules=PayrollRuleVersion::where('is_active',true)->orderByDesc('effective_from')->get();$taxRules=TaxRuleVersion::where('is_active',true)->orderByDesc('effective_from')->get();$statutoryRules=StatutoryRuleVersion::where('is_active',true)->orderByDesc('effective_from')->get();return view('human-capital.payroll.runs.create',compact('payrollRules','taxRules','statutoryRules'));}
    public function store(Request $request):RedirectResponse{$this->gate('create','payroll.calculate');$data=$request->validate(['period_code'=>'required|string|max:20|unique:payroll_periods,period_code','name'=>'required|string|max:150','payroll_type'=>'required|in:SALARY,THR,BONUS,OFF_CYCLE','cycle'=>'required|in:MONTHLY,BIWEEKLY,WEEKLY','salary_period_start'=>'required|date','salary_period_end'=>'required|date|after_or_equal:salary_period_start','attendance_cutoff_start'=>'required|date','attendance_cutoff_end'=>'required|date|after_or_equal:attendance_cutoff_start','payment_date'=>'required|date','payroll_rule_version_id'=>'nullable|exists:payroll_rule_versions,id','tax_rule_version_id'=>'nullable|exists:tax_rule_versions,id','statutory_rule_version_id'=>'nullable|exists:statutory_rule_versions,id','notes'=>'nullable|string']);$data['created_by']=auth()->id();$data['updated_by']=auth()->id();$period=PayrollPeriod::create($data);return redirect()->route('payroll.runs.show',$period)->with('success','Payroll period created.');}
    public function show(PayrollPeriod $period):View{$this->gate('view','payroll.salary.view');$period->load(['runs'=>fn($q)=>$q->orderByDesc('run_no')]);$latest=$period->runs->first();if($latest)$latest->load(['employees'=>fn($q)=>$q->orderBy('employee_code')]);return view('human-capital.payroll.runs.show',compact('period','latest'));}
    public function calculate(PayrollPeriod $period):RedirectResponse{$this->gate('edit','payroll.calculate');abort_if(in_array($period->status,['FINALIZED','POSTED'],true),422,'Finalized payroll cannot be recalculated.');$run=$this->runs->calculate($period,auth()->id());return redirect()->route('payroll.runs.result',$run)->with('success','Payroll calculation completed.');}
    public function result(PayrollRun $run):View{$this->gate('view','payroll.salary.view');$run->load(['period','employees.components','employees.traces']);return view('human-capital.payroll.runs.result',compact('run'));}
    private function gate(string $action,string $permission):void{$user=auth()->user();abort_unless($this->authz->allows($user,'payroll.runs',$action)&&$this->authz->allows($user,'payroll.runs',$permission),403);}
}
