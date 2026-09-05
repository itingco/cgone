<?php

namespace App\Http\Controllers\HumanCapital;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\SaveEmployeeAllocationRequest;
use App\Models\BusinessUnit;
use App\Models\HumanCapital\{Department, Employee, EmployeeAllocation, EmployeeGroup, EmployeeLevel, OfficeLocation, PayrollGroup, Position, SubDepartment, Team, Workgroup};
use App\Services\Audit\ActivityLogService;
use App\Services\HumanCapital\EmployeeAllocationService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class EmployeeAllocationController extends Controller
{
    public function __construct(
        private readonly MenuAuthorizationService $authz,
        private readonly EmployeeAllocationService $periods,
        private readonly ActivityLogService $audit,
    ) {
    }

    public function index(): View
    {
        $this->gate('view');
        $q = EmployeeAllocation::query()->with(['employee','businessUnit','department','position','officeLocation','payrollGroup']);
        if (request()->filled('employee_id')) $q->where('employee_id', request()->integer('employee_id'));
        $rows = $q->orderByDesc('effective_from')->orderByDesc('id')->paginate(30)->withQueryString();
        return view('human-capital.allocations.index', ['rows' => $rows, 'employees' => Employee::orderBy('employee_code')->get(['id','employee_code','full_name'])]);
    }

    public function create(): View
    {
        $this->gate('create');
        $allocation = new EmployeeAllocation(['employee_id' => request()->integer('employee_id') ?: null, 'effective_from' => now()->toDateString()]);
        return view('human-capital.allocations.form', $this->formData($allocation));
    }

    public function store(SaveEmployeeAllocationRequest $request): RedirectResponse
    {
        $this->gate('create');
        $data = $request->validated();
        $this->periods->assertNoOverlap((int)$data['employee_id'], $data['effective_from'], $data['effective_to'] ?? null);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        $allocation = EmployeeAllocation::create($data);
        $this->audit->record('hr.allocations', 'create', $allocation, [], $allocation->toArray(), ['employee_id' => $allocation->employee_id]);
        return redirect()->route('hr.employees.show', $allocation->employee_id)->with('success', 'Employee allocation added.');
    }

    public function edit(EmployeeAllocation $allocation): View
    {
        $this->gate('edit');
        return view('human-capital.allocations.form', $this->formData($allocation));
    }

    public function update(SaveEmployeeAllocationRequest $request, EmployeeAllocation $allocation): RedirectResponse
    {
        $this->gate('edit');
        $data = $request->validated();
        $this->periods->assertNoOverlap((int)$data['employee_id'], $data['effective_from'], $data['effective_to'] ?? null, $allocation->id);
        $before = $allocation->toArray();
        $data['updated_by'] = auth()->id();
        $allocation->update($data);
        $this->audit->record('hr.allocations', 'update', $allocation, $before, $allocation->fresh()->toArray(), ['employee_id' => $allocation->employee_id]);
        return redirect()->route('hr.employees.show', $allocation->employee_id)->with('success', 'Employee allocation updated.');
    }

    private function formData(EmployeeAllocation $allocation): array
    {
        $active = fn ($class) => $class::where('is_active', true)->orderBy('code')->get();
        return [
            'allocation' => $allocation,
            'employees' => Employee::where('is_active', true)->orderBy('employee_code')->get(),
            'businessUnits' => BusinessUnit::where('is_active', true)->orderBy('code')->get(),
            'departments' => $active(Department::class),
            'subDepartments' => $active(SubDepartment::class),
            'positions' => $active(Position::class),
            'levels' => $active(EmployeeLevel::class),
            'groups' => $active(EmployeeGroup::class),
            'workgroups' => $active(Workgroup::class),
            'teams' => $active(Team::class),
            'officeLocations' => $active(OfficeLocation::class),
            'payrollGroups' => $active(PayrollGroup::class),
        ];
    }

    private function gate(string $action): void
    {
        abort_unless($this->authz->allows(auth()->user(), 'hr.allocations', $action), 403);
    }
}
