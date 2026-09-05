@extends('layouts.app')
@section('title',$report?'Edit Custom Report':'New Custom Report')
@section('content')
@php
    $currentColumns=collect($definition['columns']??[])->keyBy('field');
    $currentGroups=$definition['groups']??[];
    $currentSort=collect($definition['sort']??[])->keyBy('field');
    $numericFields=collect($fields)->filter(fn($field)=>(bool)($field['numeric']??false))->values();
    $filterableFields=collect($fields)->filter(fn($field)=>(bool)($field['filter_allowed']??false))->values();
    $aggregateOptions=['SUM','COUNT','AVG','MIN','MAX'];
    $chartTypes=['column','bar','line','pie','donut'];
    $filterOperators=['equals','not_equals','contains','starts_with','ends_with','gt','gte','lt','lte','between','in','not_in','is_blank','is_not_blank'];
@endphp
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div><div class="text-muted small">{{ $source->category() }} · {{ $source->name() }}</div><h3 class="mb-1">{{ $report?'Edit Custom Report':'New Custom Report' }}</h3><div class="text-muted">Choose fields, filters, grouping, totals and an optional chart.</div></div>
    <div class="d-flex gap-2"><a class="btn btn-light" href="{{ route('reports.builder.index') }}">Builder Home</a>@if($report)<a class="btn btn-outline-secondary" href="{{ route('reports.run',$report) }}">Open Report</a>@endif</div>
</div>

<form id="builder-form" method="post" action="{{ $report?route('reports.builder.update',$report):route('reports.builder.store') }}">
    @csrf @if($report) @method('PUT') @endif
    <input type="hidden" name="datasource" value="{{ $source->code() }}">
    <input type="hidden" name="definition_json" id="definition-json">

    <div class="card mb-3"><div class="card-body"><div class="row g-3">
        <div class="col-md-5"><label class="form-label">Report Name</label><input class="form-control" name="name" value="{{ old('name',$report?->name) }}" required></div>
        <div class="col-md-3"><label class="form-label">Category</label><input class="form-control" name="category" value="{{ old('category',$report?->category ?: 'Custom') }}" required></div>
        <div class="col-md-4"><label class="form-label">Data Source</label><input class="form-control" value="{{ $source->name() }}" disabled></div>
        <div class="col-12"><label class="form-label">Description</label><input class="form-control" name="description" value="{{ old('description',data_get($definition,'description')) }}"></div>
    </div></div></div>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-3">
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header bg-white"><strong>Available Fields</strong></div>
                <div class="card-body" style="max-height:650px;overflow:auto">
                    @include('reports.builder.partials.field-list')
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            <div class="card mb-3"><div class="card-header bg-white"><strong>Columns & Aggregation</strong></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Use</th><th>Field</th><th>Aggregate</th><th>Group</th><th>Sort</th></tr></thead><tbody>
                @foreach($fields as $f)
                    @php $cc=$currentColumns->get($f['key']); $ss=$currentSort->get($f['key']); @endphp
                    <tr data-field-row="{{ $f['key'] }}">
                        <td><input class="form-check-input js-column" type="checkbox" value="{{ $f['key'] }}" @checked($cc)></td>
                        <td><strong>{{ $f['label'] }}</strong><div class="small text-muted">{{ $f['group_label'] }}</div></td>
                        <td>
                            <select class="form-select form-select-sm js-aggregate" @disabled(!$f['aggregate_allowed'])>
                                <option value="">None</option>
                                @foreach($aggregateOptions as $a)
                                    <option value="{{ $a }}" @selected(($cc['aggregate']??null)===$a)>{{ $a }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input class="form-check-input js-group" type="checkbox"
                                   @checked(in_array($f['key'],$currentGroups,true))
                                   @disabled(!$f['group_allowed'])>
                        </td>
                        <td>
                            <select class="form-select form-select-sm js-sort" @disabled(!$f['sort_allowed'])>
                                <option value="">-</option>
                                <option value="asc" @selected(($ss['direction']??null)==='asc')>ASC</option>
                                <option value="desc" @selected(($ss['direction']??null)==='desc')>DESC</option>
                            </select>
                        </td>
                    </tr>
                @endforeach
            </tbody></table></div></div>

            <div class="card mb-3"><div class="card-header bg-white d-flex justify-content-between"><strong>Filters</strong><button class="btn btn-sm btn-outline-primary" type="button" id="add-filter">+ Filter</button></div><div class="card-body">
                <div class="d-flex gap-3 mb-2"><label><input type="radio" name="filter_mode_ui" value="AND" @checked(($definition['filter_mode']??'AND')==='AND')> Match ALL</label><label><input type="radio" name="filter_mode_ui" value="OR" @checked(($definition['filter_mode']??'AND')==='OR')> Match ANY</label></div>
                <div id="filter-box"></div>
                <div class="small text-muted">For Between use two values separated by comma. For IN / NOT IN use comma-separated values.</div>
            </div></div>

            <div class="card mb-3"><div class="card-header bg-white"><strong>Calculated Fields</strong></div><div class="card-body">
                <div class="small text-muted mb-2">Safe arithmetic only. Calculations are evaluated from selected output fields; SQL expressions are not accepted.</div>
                <div id="calc-box"></div><button class="btn btn-sm btn-outline-primary" type="button" id="add-calc">+ Calculation</button>
            </div></div>

            <div class="card mb-3"><div class="card-header bg-white"><strong>Summary & Chart</strong></div><div class="card-body"><div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Summary Fields</label>
                    <select class="form-select" id="summary-fields" multiple size="5">
                        @foreach($numericFields as $f)
                            <option value="{{ $f['key'] }}" @selected(collect($definition['summary']??[])->pluck('field')->contains($f['key']))>{{ $f['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6"><div class="row g-2">
                    <div class="col-12"><label class="form-label">Chart Type</label><select class="form-select" id="chart-type"><option value="">No Chart</option>@foreach($chartTypes as $t)<option value="{{ $t }}" @selected(($definition['chart']['type']??'')===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label">Label Field</label><select class="form-select" id="chart-label"><option value="">-</option>@foreach($fields as $f)<option value="{{ $f['key'] }}" @selected(($definition['chart']['label_field']??'')===$f['key'])>{{ $f['label'] }}</option>@endforeach</select></div>
                    <div class="col-6"><label class="form-label">Value Field</label><select class="form-select" id="chart-value"><option value="">-</option>@foreach($numericFields as $f)<option value="{{ $f['key'] }}" @selected(($definition['chart']['value_field']??'')===$f['key'])>{{ $f['label'] }}</option>@endforeach</select></div>
                </div></div>
            </div></div></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3"><button class="btn btn-primary" type="submit">Save Report</button><button class="btn btn-outline-secondary" type="button" id="preview-btn">Preview</button></div>
</form>

<div class="mt-4" id="preview-target"></div>

<template id="filter-template"><div class="row g-2 align-items-end mb-2 filter-row"><div class="col-md-4"><label class="form-label small">Field</label><select class="form-select form-select-sm filter-field">@foreach($filterableFields as $f)<option value="{{ $f['key'] }}">{{ $f['label'] }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label small">Operator</label><select class="form-select form-select-sm filter-operator">@foreach($filterOperators as $op)<option value="{{ $op }}">{{ str_replace('_',' ',strtoupper($op)) }}</option>@endforeach</select></div><div class="col-md-4"><label class="form-label small">Value</label><input class="form-control form-control-sm filter-value"></div><div class="col-md-1"><button class="btn btn-sm btn-outline-danger remove-row" type="button">×</button></div></div></template>
<template id="calc-template"><div class="row g-2 align-items-end mb-2 calc-row"><div class="col-md-2"><label class="small form-label">Key</label><input class="form-control form-control-sm calc-key" placeholder="margin_pct"></div><div class="col-md-2"><label class="small form-label">Label</label><input class="form-control form-control-sm calc-label"></div><div class="col-md-2"><label class="small form-label">Left field</label><select class="form-select form-select-sm calc-left">@foreach($numericFields as $f)<option value="{{ $f['key'] }}">{{ $f['label'] }}</option>@endforeach</select></div><div class="col-md-2"><label class="small form-label">Operation</label><select class="form-select form-select-sm calc-op"><option value="add">+</option><option value="subtract">−</option><option value="multiply">×</option><option value="divide">÷</option></select></div><div class="col-md-2"><label class="small form-label">Right field</label><select class="form-select form-select-sm calc-right">@foreach($numericFields as $f)<option value="{{ $f['key'] }}">{{ $f['label'] }}</option>@endforeach</select></div><div class="col-md-1"><label class="small form-label">×</label><input class="form-control form-control-sm calc-mult" type="number" step="0.0001" value="1"></div><div class="col-md-1"><button class="btn btn-sm btn-outline-danger remove-row" type="button">×</button></div></div></template>

@push('scripts')
<script>
(()=>{
const fields=@json(collect($fields)->keyBy('key'));
const initial=@json($definition);
const filterBox=document.getElementById('filter-box'), calcBox=document.getElementById('calc-box');

function addFilter(data={}){
 const el=document.getElementById('filter-template').content.firstElementChild.cloneNode(true);
 el.querySelector('.filter-field').value=data.field||Object.keys(fields)[0]||'';
 el.querySelector('.filter-operator').value=data.operator||'equals';
 let value=data.value??'';
 if(Array.isArray(value)) value=value.join(',');
 el.querySelector('.filter-value').value=value;
 el.querySelector('.remove-row').onclick=()=>el.remove();
 filterBox.appendChild(el);
}
function addCalc(data={}){
 const el=document.getElementById('calc-template').content.firstElementChild.cloneNode(true);
 el.querySelector('.calc-key').value=data.key||'';
 el.querySelector('.calc-label').value=data.label||'';
 el.querySelector('.calc-left').value=data.expression?.left?.field||'';
 el.querySelector('.calc-op').value=data.expression?.op||'subtract';
 el.querySelector('.calc-right').value=data.expression?.right?.field||'';
 el.querySelector('.calc-mult').value=data.expression?.multiply_by??1;
 el.querySelector('.remove-row').onclick=()=>el.remove();
 calcBox.appendChild(el);
}
(initial.filters||[]).forEach(addFilter); (initial.calculations||[]).forEach(addCalc);
document.getElementById('add-filter').onclick=()=>addFilter();
document.getElementById('add-calc').onclick=()=>addCalc();

function definition(){
 const columns=[],groups=[],sort=[];
 document.querySelectorAll('[data-field-row]').forEach(row=>{
   const key=row.dataset.fieldRow,meta=fields[key];
   if(row.querySelector('.js-column').checked) columns.push({field:key,label:meta.label,aggregate:row.querySelector('.js-aggregate').value||null,type:meta.format||meta.data_type});
   const g=row.querySelector('.js-group'); if(g&&g.checked)groups.push(key);
   const s=row.querySelector('.js-sort'); if(s&&s.value)sort.push({field:key,direction:s.value});
 });
 const filters=[...document.querySelectorAll('.filter-row')].map(r=>{let v=r.querySelector('.filter-value').value;const op=r.querySelector('.filter-operator').value;if(['between','in','not_in'].includes(op))v=v.split(',').map(x=>x.trim()).filter(Boolean);return {field:r.querySelector('.filter-field').value,operator:op,value:v};});
 const calculations=[...document.querySelectorAll('.calc-row')].filter(r=>r.querySelector('.calc-key').value.trim()).map(r=>({key:r.querySelector('.calc-key').value.trim(),label:r.querySelector('.calc-label').value.trim()||r.querySelector('.calc-key').value.trim(),type:'number',expression:{op:r.querySelector('.calc-op').value,left:{field:r.querySelector('.calc-left').value},right:{field:r.querySelector('.calc-right').value},multiply_by:Number(r.querySelector('.calc-mult').value||1)}}));
 const summary=[...document.getElementById('summary-fields').selectedOptions].map(o=>({field:o.value,label:o.text}));
 const chartType=document.getElementById('chart-type').value;
 const chart=chartType?{type:chartType,label_field:document.getElementById('chart-label').value,value_field:document.getElementById('chart-value').value}:{};
 return {datasource:@json($source->code()),columns,filters,filter_mode:document.querySelector('input[name="filter_mode_ui"]:checked')?.value||'AND',groups,sort,calculations,summary,chart};
}
document.getElementById('builder-form').addEventListener('submit',()=>document.getElementById('definition-json').value=JSON.stringify(definition()));
document.getElementById('preview-btn').onclick=async()=>{
 const body=new FormData();body.append('_token',@json(csrf_token()));body.append('datasource',@json($source->code()));body.append('definition_json',JSON.stringify(definition()));
 const target=document.getElementById('preview-target');target.innerHTML='<div class="text-muted">Loading preview...</div>';
 const res=await fetch(@json(route('reports.builder.preview')),{method:'POST',body,headers:{'X-Requested-With':'XMLHttpRequest'}});
 if(res.ok){target.innerHTML=await res.text();}else{let msg='Preview failed.';try{const j=await res.json();msg=Object.values(j.errors||{}).flat().join('<br>')||j.message||msg}catch(e){}target.innerHTML='<div class="alert alert-danger">'+msg+'</div>';}
};
})();
</script>
@endpush
@endsection
