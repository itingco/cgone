<?php
namespace App\Http\Controllers;

use App\Models\DashboardPreference;
use App\Services\Dashboard\DashboardWidgetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DashboardSettingsController extends Controller
{
    public function __construct(){ $this->middleware('menu.permission:dashboard,view'); }

    public function edit(DashboardWidgetService $widgets)
    {
        $current = DashboardPreference::query()->where('user_id',auth()->id())->get()->keyBy('widget_key');
        return view('dashboard-settings',['registry'=>$widgets->registry(),'current'=>$current]);
    }

    public function update(Request $request, DashboardWidgetService $widgets)
    {
        $allowed = array_keys($widgets->registry());
        $data = $request->validate([
            'widgets'=>['array'],
            'widgets.*.key'=>['required',Rule::in($allowed)],
            'widgets.*.enabled'=>['nullable','boolean'],
            'widgets.*.sort_order'=>['nullable','integer','min:1','max:999'],
            'widgets.*.width'=>['nullable','integer','min:2','max:12'],
        ]);
        DB::transaction(function () use ($data) {
            DashboardPreference::where('user_id',auth()->id())->delete();
            foreach (($data['widgets'] ?? []) as $i=>$row) {
                if (empty($row['enabled'])) continue;
                DashboardPreference::create([
                    'user_id'=>auth()->id(), 'widget_key'=>$row['key'],
                    'sort_order'=>$row['sort_order'] ?? (($i+1)*10),
                    'width'=>$row['width'] ?? 3, 'is_enabled'=>true,
                ]);
            }
        });
        return redirect()->route('dashboard')->with('success','Dashboard layout berhasil disimpan.');
    }
}
