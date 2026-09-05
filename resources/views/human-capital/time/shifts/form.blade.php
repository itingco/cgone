@extends('layouts.app')
@section('title',$shift->exists?'Edit Shift':'New Shift')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">{{ $shift->exists?'Edit':'New' }} Shift</h4><a class="btn btn-outline-secondary" href="{{ route('hr.shifts.index') }}">Back</a></div>
<div class="card"><div class="card-body">
<form method="post" action="{{ $shift->exists?route('hr.shifts.update',$shift):route('hr.shifts.store') }}">@csrf @if($shift->exists) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Code</label><input class="form-control" name="code" value="{{ old('code',$shift->code) }}" required></div>
<div class="col-md-5"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name',$shift->name) }}" required></div>
<div class="col-md-2"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" value="{{ old('start_time',$shift->start_time?substr($shift->start_time,0,5):'08:00') }}" required></div>
<div class="col-md-2"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" value="{{ old('end_time',$shift->end_time?substr($shift->end_time,0,5):'17:00') }}" required></div>
<div class="col-md-3"><label class="form-label">Break Minutes</label><input type="number" class="form-control" name="break_minutes" min="0" value="{{ old('break_minutes',$shift->break_minutes??60) }}"></div>
<div class="col-md-3"><label class="form-label">Late Grace Minutes</label><input type="number" class="form-control" name="grace_late_minutes" min="0" value="{{ old('grace_late_minutes',$shift->grace_late_minutes??0) }}"></div>
<div class="col-md-3"><label class="form-label">Standard Work Minutes</label><input type="number" class="form-control" name="standard_work_minutes" min="1" value="{{ old('standard_work_minutes',$shift->standard_work_minutes??480) }}"></div>
<div class="col-md-3 d-flex align-items-end"><div><div class="form-check"><input type="hidden" name="overtime_eligible" value="0"><input class="form-check-input" type="checkbox" name="overtime_eligible" value="1" @checked(old('overtime_eligible',$shift->overtime_eligible??true))><label class="form-check-label">Overtime Eligible</label></div><div class="form-check"><input type="hidden" name="cross_day" value="0"><input class="form-check-input" type="checkbox" name="cross_day" value="1" @checked(old('cross_day',$shift->cross_day??false))><label class="form-check-label">Cross Day</label></div><div class="form-check"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$shift->is_active??true))><label class="form-check-label">Active</label></div></div></div>
</div><div class="mt-4"><button class="btn btn-primary">Save Shift</button></div></form>
</div></div>
@endsection
