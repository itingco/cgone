@extends('layouts.app')
@section('title',$report?'Edit SQL Report':'New SQL Report')
@section('content')
@php
    $parameters=(array)data_get($definition,'parameters',[]);
    if(old('parameters_json')){ $decoded=json_decode((string)old('parameters_json'),true); if(is_array($decoded)) $parameters=$decoded; }
@endphp
<div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><div class="text-muted small">IT / Administrator</div><h3 class="mb-1">{{ $report?'Edit':'New' }} Advanced SQL Report</h3><div class="text-muted">Only SELECT / WITH queries are accepted. Use named parameters such as <code>:start_date</code>.</div></div><a class="btn btn-light" href="{{ route('reports.sql.index') }}">Back</a></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="post" action="{{ $report?route('reports.sql.update',$report):route('reports.sql.store') }}" id="sql-report-form">
@csrf
@if($report)
    @method('PUT')
@endif
<div class="card mb-3"><div class="card-body"><div class="row g-3">
<div class="col-md-5"><label class="form-label">Report Name</label><input class="form-control" name="name" value="{{ old('name',$report?->name) }}" required></div>
<div class="col-md-3"><label class="form-label">Category</label><input class="form-control" name="category" value="{{ old('category',$report?->category??'Custom SQL') }}" required></div>
<div class="col-12"><label class="form-label">Description</label><input class="form-control" name="description" value="{{ old('description',data_get($definition,'description')) }}"></div>
</div></div></div>
<div class="card mb-3"><div class="card-header bg-white"><strong>SQL Query</strong></div><div class="card-body"><textarea class="form-control font-monospace" name="sql" rows="14" spellcheck="false" required>{{ old('sql',data_get($definition,'sql','SELECT 1 AS sample')) }}</textarea><div class="small text-muted mt-2">Blocked: INSERT, UPDATE, DELETE, MERGE, DROP, ALTER, TRUNCATE, CREATE, COPY, CALL, DO, transaction commands, multiple statements, and row-locking SELECT clauses.</div></div></div>
<div class="card mb-3"><div class="card-header bg-white d-flex justify-content-between"><strong>Parameters</strong><button class="btn btn-sm btn-outline-primary" type="button" id="add-param">+ Parameter</button></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Name</th><th>Label</th><th>Type</th><th>Lookup Table</th><th>Default</th><th>Required</th><th>Sensitive</th><th></th></tr></thead><tbody id="param-box"></tbody></table></div><div class="card-body border-top small text-muted">Use <code>:parameter_name</code> inside SQL. Lookup tables are restricted to safe master tables. Sensitive values are masked in execution logs.</div></div>
<input type="hidden" name="parameters_json" id="parameters-json">
<div class="d-flex gap-2">
    <button class="btn btn-primary">{{ $report?'Save Changes':'Create SQL Report' }}</button>
    <button type="button" class="btn btn-outline-dark" id="preview-sql">Preview SQL</button>
    @if($report)
        <a class="btn btn-outline-secondary" href="{{ route('reports.run',$report) }}">Run Report</a>
    @endif
</div>
<div id="sql-preview"></div>
</form>
<template id="param-template"><tr><td><input class="form-control form-control-sm p-name" placeholder="start_date"></td><td><input class="form-control form-control-sm p-label" placeholder="Start Date"></td><td><select class="form-select form-select-sm p-type"><option>string</option><option>integer</option><option>decimal</option><option>date</option><option>datetime</option><option>boolean</option><option>lookup</option><option>business_unit</option></select></td><td><select class="form-select form-select-sm p-table"><option value="">-</option>@foreach(['customers','vendors','items','item_categories','brands','locations','location_bins','price_levels','users','chart_of_accounts'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach</select></td><td><input class="form-control form-control-sm p-default"></td><td class="text-center"><input type="checkbox" class="form-check-input p-required"></td><td class="text-center"><input type="checkbox" class="form-check-input p-sensitive"></td><td><button type="button" class="btn btn-sm btn-outline-danger p-remove">×</button></td></tr></template>
@push('scripts')
<script>
(()=>{const initial=@json($parameters);const box=document.getElementById('param-box'),tpl=document.getElementById('param-template'),hidden=document.getElementById('parameters-json');
function add(p={}){const frag=tpl.content.cloneNode(true);const r=frag.querySelector('tr');r.querySelector('.p-name').value=p.name||'';r.querySelector('.p-label').value=p.label||'';r.querySelector('.p-type').value=p.type||'string';r.querySelector('.p-table').value=p.table||'';r.querySelector('.p-default').value=p.default??'';r.querySelector('.p-required').checked=!!p.required;r.querySelector('.p-sensitive').checked=!!p.sensitive;box.append(frag)}
function serialize(){hidden.value=JSON.stringify([...box.querySelectorAll('tr')].map(r=>({name:r.querySelector('.p-name').value.trim(),label:r.querySelector('.p-label').value.trim(),type:r.querySelector('.p-type').value,table:r.querySelector('.p-table').value||null,default:r.querySelector('.p-default').value===''?null:r.querySelector('.p-default').value,required:r.querySelector('.p-required').checked,sensitive:r.querySelector('.p-sensitive').checked})).filter(x=>x.name));}
initial.forEach(add);document.getElementById('add-param').onclick=()=>add();box.addEventListener('click',e=>{if(e.target.classList.contains('p-remove'))e.target.closest('tr').remove()});document.getElementById('sql-report-form').addEventListener('submit',serialize);document.getElementById('preview-sql').onclick=async()=>{serialize();const form=document.getElementById('sql-report-form'),fd=new FormData();fd.append('_token',form.querySelector('[name=_token]').value);fd.append('sql',form.querySelector('[name=sql]').value);fd.append('parameters_json',hidden.value);const out=document.getElementById('sql-preview');out.innerHTML='<div class=\"alert alert-light border mt-3\">Running preview...</div>';const r=await fetch(@json(route('reports.sql.preview')),{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});out.innerHTML=await r.text();};serialize();})();
</script>
@endpush
@endsection
