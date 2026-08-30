@extends('layouts.app')
@section('title',$isEdit?'Edit Department':'New Department')
@section('content')
<div class="card"><div class="card-body"><form method="post" action="{{ $isEdit?route('config.departments.update',$record):route('config.departments.store') }}">@csrf @if($isEdit)@method('PUT')@endif
<div class="row g-3"><div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" value="{{ old('code',$record->code) }}" required></div><div class="col-md-8"><label class="form-label">Department Name</label><input class="form-control" name="name" value="{{ old('name',$record->name) }}" required></div><div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3">{{ old('description',$record->description) }}</textarea></div><div class="col-12"><input type="hidden" name="is_active" value="0"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked((bool)old('is_active',$record->exists?$record->is_active:true))><span class="form-check-label">Active</span></label></div></div>
<div class="mt-4"><button class="btn btn-primary">Save</button> <a class="btn btn-outline-secondary" href="{{ route('config.departments.index') }}">Cancel</a></div></form></div></div>
@endsection
