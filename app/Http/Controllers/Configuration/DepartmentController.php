<?php
namespace App\Http\Controllers\Configuration;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('menu.permission:config.departments,view')->only('index');
        $this->middleware('menu.permission:config.departments,create')->only(['create','store']);
        $this->middleware('menu.permission:config.departments,edit')->only(['edit','update']);
    }

    public function index(Request $request)
    {
        $rows = Department::query()
            ->withCount('users')
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = (string) $request->input('q');
                $q->where(fn ($x) => $x->where('code','like',"%{$term}%")->orWhere('name','like',"%{$term}%"));
            })
            ->orderBy('code')->paginate(30)->withQueryString();
        return view('configuration.departments.index', compact('rows'));
    }

    public function create(){ return view('configuration.departments.form',['record'=>new Department,'isEdit'=>false]); }

    public function store(Request $request, ActivityLogService $audit)
    {
        $data = $this->validated($request);
        $row = Department::create($data);
        $audit->record('config.departments','create',$row,[],$row->toArray());
        return redirect()->route('config.departments.index')->with('success','Department created.');
    }

    public function edit(Department $department){ return view('configuration.departments.form',['record'=>$department,'isEdit'=>true]); }

    public function update(Request $request, Department $department, ActivityLogService $audit)
    {
        $data = $this->validated($request, $department->id);
        $before = $department->toArray();
        $department->update($data);
        $audit->record('config.departments','update',$department,$before,$department->fresh()->toArray());
        return redirect()->route('config.departments.index')->with('success','Department updated.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'code' => ['required','string','max:50',Rule::unique('departments','code')->ignore($id)],
            'name' => ['required','string','max:150'],
            'description' => ['nullable','string'],
            'is_active' => ['required','boolean'],
        ]);
    }
}
