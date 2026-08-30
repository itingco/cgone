@extends('layouts.app')
@section('title',$isEdit?'Edit User':'New User')
@section('content')
<div class="card"><div class="card-body"><form method="post" action="{{ $isEdit?route('config.users.update',$record):route('config.users.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name',$record->name) }}" required></div>
<div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="{{ old('email',$record->email) }}" required></div>
<div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">- No Department -</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string)old('department_id',$record->department_id)===(string)$department->id)>{{ $department->code }} - {{ $department->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Password {{ $isEdit?'(leave blank to keep)':'' }}</label><input type="password" class="form-control" name="password" @if(!$isEdit) required @endif></div>
<div class="col-12"><div class="d-flex justify-content-between align-items-center mb-2"><label class="form-label mb-0">Roles</label><div class="btn-group btn-group-sm"><button class="btn btn-outline-secondary" type="button" id="roles-select-all">Select All</button><button class="btn btn-outline-secondary" type="button" id="roles-clear-all">Clear All</button></div></div>
<div class="row g-2">@php $selected=old('role_ids',$record->roles?->pluck('id')->all()??[]); @endphp @foreach($roles as $role)<div class="col-md-4 col-lg-3"><label class="border rounded p-2 w-100 h-100 d-flex gap-2 align-items-start"><input class="form-check-input role-checkbox" type="checkbox" name="role_ids[]" value="{{ $role->id }}" @checked(in_array($role->id,$selected))><span><strong>{{ $role->name }}</strong><br><small class="text-muted">{{ $role->code }}</small></span></label></div>@endforeach</div></div>
<div class="col-12"><input type="hidden" name="is_active" value="0"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool)old('is_active',$record->exists?$record->is_active:true))><span class="form-check-label">Active</span></label></div>
</div><div class="mt-4"><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="{{ route('config.users.index') }}">Cancel</a></div></form></div></div>
@endsection
@push('scripts')<script>document.getElementById('roles-select-all')?.addEventListener('click',()=>document.querySelectorAll('.role-checkbox').forEach(x=>x.checked=true));document.getElementById('roles-clear-all')?.addEventListener('click',()=>document.querySelectorAll('.role-checkbox').forEach(x=>x.checked=false));</script>@endpush
