@extends('layouts.app')
@section('title','Shifts')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div><h4 class="mb-1">Shift Master</h4><div class="small text-muted">Define planned working hours, grace period, break, and cross-day behavior.</div></div>
    <a class="btn btn-primary" href="{{ route('hr.shifts.create') }}">+ New Shift</a>
</div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Code</th><th>Name</th><th>Time</th><th>Break</th><th>Grace</th><th>Std. Work</th><th>OT</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($rows as $shift)
<tr><td class="fw-semibold">{{ $shift->code }}</td><td>{{ $shift->name }}</td><td>{{ substr($shift->start_time,0,5) }} → {{ substr($shift->end_time,0,5) }} @if($shift->cross_day)<span class="badge text-bg-info">+1 day</span>@endif</td><td>{{ $shift->break_minutes }} min</td><td>{{ $shift->grace_late_minutes }} min</td><td>{{ $shift->standard_work_minutes }} min</td><td>{{ $shift->overtime_eligible?'Yes':'No' }}</td><td><span class="badge {{ $shift->is_active?'text-bg-success':'text-bg-secondary' }}">{{ $shift->is_active?'ACTIVE':'INACTIVE' }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('hr.shifts.edit',$shift) }}">Edit</a></td></tr>
@empty<tr><td colspan="9" class="text-center text-muted py-4">No shift data.</td></tr>@endforelse
</tbody></table></div></div><div class="mt-3">{{ $rows->links() }}</div>
@endsection
