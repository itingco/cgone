@extends('layouts.app')
@section('title',($row->exists?'Edit ':'New ').$title)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h4 class="mb-0">{{ $row->exists?'Edit':'New' }} {{ $title }}</h4><a class="btn btn-outline-secondary" href="{{ route('hr.organization.index',$type) }}">Back</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ $row->exists?route('hr.organization.update',[$type,$row->id]):route('hr.organization.store',$type) }}">@csrf @if($row->exists)@method('PUT')@endif
<div class="card"><div class="card-body row g-3">
<div class="col-md-3"><label class="form-label">Code</label><input class="form-control" name="code" value="{{ old('code',$row->code) }}" required></div>
<div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name',$row->name) }}" required></div>
@if($type==='sub-departments')<div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected((string)old('department_id',$row->department_id)===(string)$d->id)>{{ $d->code }} - {{ $d->name }}</option>@endforeach</select></div>@endif
<div class="col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$row->exists?$row->is_active:true))><label class="form-check-label">Active</label></div></div>
</div><div class="card-footer bg-white"><button class="btn btn-primary">Save</button></div></div></form>
@endsection
