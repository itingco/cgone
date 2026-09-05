<?php

namespace App\Http\Controllers\HumanCapital;

use App\Http\Controllers\Controller;
use App\Http\Requests\HumanCapital\SaveOrganizationRequest;
use App\Models\HumanCapital\{Department, EmployeeGroup, EmployeeLevel, OfficeLocation, PayrollGroup, Position, SubDepartment, Team, Workgroup};
use App\Services\Audit\ActivityLogService;
use App\Services\Security\MenuAuthorizationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class OrganizationController extends Controller
{
    private array $types = [
        'departments' => [Department::class, 'Departments', 'hr.departments'],
        'sub-departments' => [SubDepartment::class, 'Sub Departments', 'hr.sub-departments'],
        'positions' => [Position::class, 'Positions / Job Titles', 'hr.positions'],
        'levels' => [EmployeeLevel::class, 'Employee Levels', 'hr.levels'],
        'groups' => [EmployeeGroup::class, 'Employee Groups', 'hr.groups'],
        'workgroups' => [Workgroup::class, 'Workgroups', 'hr.workgroups'],
        'teams' => [Team::class, 'Teams', 'hr.teams'],
        'office-locations' => [OfficeLocation::class, 'Office Locations', 'hr.office-locations'],
        'payroll-groups' => [PayrollGroup::class, 'Payroll Groups', 'hr.payroll-groups'],
    ];

    public function __construct(private readonly MenuAuthorizationService $authz, private readonly ActivityLogService $audit)
    {
    }

    public function index(string $type): View
    {
        [$class, $title, $menu] = $this->cfg($type);
        $this->gate($menu, 'view');
        $query = $class::query();
        if ($type === 'sub-departments') $query->with('department');
        if (request()->filled('q')) {
            $q = (string) request('q');
            $query->where(fn ($x) => $x->where('code', 'ilike', "%{$q}%")->orWhere('name', 'ilike', "%{$q}%"));
        }
        $rows = $query->orderByDesc('is_active')->orderBy('code')->paginate(30)->withQueryString();
        return view('human-capital.organization.index', compact('type','title','rows'));
    }

    public function create(string $type): View
    {
        [$class, $title, $menu] = $this->cfg($type);
        $this->gate($menu, 'create');
        return view('human-capital.organization.form', $this->formData($type, $title, new $class(['is_active' => true])));
    }

    public function store(SaveOrganizationRequest $request, string $type): RedirectResponse
    {
        [$class, $title, $menu] = $this->cfg($type);
        $this->gate($menu, 'create');
        $data = $this->cleanData($request, $type, $class);
        $row = $class::create($data);
        $this->audit->record($menu, 'create', $row, [], $row->toArray(), ['code' => $row->code]);
        return redirect()->route('hr.organization.index', $type)->with('success', $title.' created.');
    }

    public function edit(string $type, int $id): View
    {
        [$class, $title, $menu] = $this->cfg($type);
        $this->gate($menu, 'edit');
        $row = $class::findOrFail($id);
        return view('human-capital.organization.form', $this->formData($type, $title, $row));
    }

    public function update(SaveOrganizationRequest $request, string $type, int $id): RedirectResponse
    {
        [$class, $title, $menu] = $this->cfg($type);
        $this->gate($menu, 'edit');
        $row = $class::findOrFail($id);
        $before = $row->toArray();
        $row->update($this->cleanData($request, $type, $class, $row));
        $this->audit->record($menu, 'update', $row, $before, $row->fresh()->toArray(), ['code' => $row->code]);
        return back()->with('success', $title.' updated.');
    }

    private function cfg(string $type): array
    {
        if (! isset($this->types[$type])) abort(404);
        return $this->types[$type];
    }

    private function gate(string $menu, string $action): void
    {
        abort_unless($this->authz->allows(auth()->user(), $menu, $action), 403);
    }

    private function cleanData(SaveOrganizationRequest $request, string $type, string $class, ?Model $ignore = null): array
    {
        $data = $request->validated();
        $duplicate = $class::query()->where('code', $data['code']);
        if ($ignore) $duplicate->where('id', '!=', $ignore->getKey());
        if ($duplicate->exists()) throw ValidationException::withMessages(['code' => 'Code already exists.']);
        if ($type !== 'sub-departments') unset($data['department_id']);
        return $data;
    }

    private function formData(string $type, string $title, Model $row): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'row' => $row,
            'departments' => $type === 'sub-departments' ? Department::where('is_active', true)->orderBy('code')->get() : collect(),
        ];
    }
}
