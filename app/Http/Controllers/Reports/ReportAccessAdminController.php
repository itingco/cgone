<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\{Role,User};
use App\Models\Reports\{ReportDefinition,ReportRoleAccess,ReportUserAccess};
use App\Services\Reports\ReportAccessService;
use Illuminate\Http\Request;

final class ReportAccessAdminController extends Controller
{
    public function __construct(private readonly ReportAccessService $access){}

    public function edit(Request $request, ReportDefinition $report)
    {
        abort_unless($this->access->allows($request->user(),$report,'share'),403);

        $roles=Role::where('is_active',true)->orderBy('name')->get();
        $users=User::where('is_active',true)->orderBy('name')->get();
        $roleAccess=$report->roleAccess()->get()->keyBy('role_id');
        $userAccess=$report->userAccess()->get()->keyBy('user_id');

        return view('reports.access.edit',compact('report','roles','users','roleAccess','userAccess'));
    }

    public function update(Request $request, ReportDefinition $report)
    {
        abort_unless($this->access->allows($request->user(),$report,'share'),403);

        $data=$request->validate([
            'visibility'=>'required|in:PRIVATE,SHARED,COMPANY',
            'roles'=>'nullable|array',
            'roles.*'=>'array',
            'users'=>'nullable|array',
            'users.*'=>'array',
        ]);

        $permissions=['view','export','print','edit','share','clone','delete','manage'];

        $report->update(['visibility'=>$data['visibility'],'updated_by'=>$request->user()->id]);

        ReportRoleAccess::where('report_definition_id',$report->id)->delete();
        foreach((array)($data['roles']??[]) as $roleId=>$grants){
            if(!Role::whereKey($roleId)->where('is_active',true)->exists()) continue;
            $row=['report_definition_id'=>$report->id,'role_id'=>$roleId];
            foreach($permissions as $p)$row['can_'.$p]=in_array($p,(array)$grants,true);
            if($row['can_view'] || collect($permissions)->contains(fn($p)=>$row['can_'.$p])) ReportRoleAccess::create($row);
        }

        ReportUserAccess::where('report_definition_id',$report->id)->delete();
        foreach((array)($data['users']??[]) as $userId=>$grants){
            if(!User::whereKey($userId)->where('is_active',true)->exists()) continue;
            $row=['report_definition_id'=>$report->id,'user_id'=>$userId];
            foreach($permissions as $p)$row['can_'.$p]=in_array($p,(array)$grants,true);
            if($row['can_view'] || collect($permissions)->contains(fn($p)=>$row['can_'.$p])) ReportUserAccess::create($row);
        }

        return redirect()->route('reports.run',$report)->with('success','Report access updated.');
    }
}
