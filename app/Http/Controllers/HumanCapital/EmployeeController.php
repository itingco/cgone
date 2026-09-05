<?php

namespace App\Http\Controllers\HumanCapital;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\SaveEmployeeRequest;
use App\Models\HumanCapital\Employee;
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EmployeeController extends Controller
{
    public function __construct(private readonly MenuAuthorizationService $authz, private readonly ActivityLogService $audit)
    {
    }

    public function index(): View
    {
        $this->gate('view');
        $q = Employee::query();
        if (request()->filled('q')) {
            $s = (string) request('q');
            $q->where(fn ($x) => $x->where('employee_code', 'ilike', "%{$s}%")->orWhere('full_name', 'ilike', "%{$s}%"));
        }
        if (request()->filled('status')) $q->where('is_active', request('status') === 'active');
        $rows = $q->orderByDesc('is_active')->orderBy('employee_code')->paginate(30)->withQueryString();
        return view('human-capital.employees.index', compact('rows'));
    }

    public function create(): View
    {
        $this->gate('create');
        return view('human-capital.employees.form', ['employee' => new Employee(['is_active' => true])]);
    }

    public function store(SaveEmployeeRequest $request): RedirectResponse
    {
        $this->gate('create');
        $employee = Employee::create($request->validated());
        $this->audit->record('hr.employees', 'create', $employee, [], $employee->toArray(), ['employee_code' => $employee->employee_code]);
        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee created.');
    }

    public function show(Employee $employee): View
    {
        $this->gate('view');
        $employee->load(['allocations.businessUnit','allocations.department','allocations.subDepartment','allocations.position','allocations.officeLocation','allocations.payrollGroup']);
        return view('human-capital.employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $this->gate('edit');
        return view('human-capital.employees.form', compact('employee'));
    }

    public function update(SaveEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->gate('edit');
        $before = $employee->toArray();
        $employee->update($request->validated());
        $this->audit->record('hr.employees', 'update', $employee, $before, $employee->fresh()->toArray(), ['employee_code' => $employee->employee_code]);
        return back()->with('success', 'Employee updated.');
    }

    private function gate(string $action): void
    {
        abort_unless($this->authz->allows(auth()->user(), 'hr.employees', $action), 403);
    }
}
