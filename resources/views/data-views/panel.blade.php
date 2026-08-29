@php
    $state = $dataViewState;
    $activeView = $state['view'];
    $dataViewAuth = app(\App\Services\Security\MenuAuthorizationService::class);
    $canCompanyView = $dataViewAuth->allows(auth()->user(),'config.data-views','edit');
    $resetUrl = $resetUrl ?? url()->current();
    $showQuickSearch = $showQuickSearch ?? false;
    $panelId = 'data-view-options-'.substr(md5($moduleKey),0,10);
    $activeFilterCount = collect($state['filters'] ?? [])->filter(fn($filter) => is_array($filter) && filled($filter['value'] ?? null))->count();
    $activeSortCount = collect($state['sort'] ?? [])->filter(fn($sort) => is_array($sort) && filled($sort['field'] ?? null))->count();
    $advancedOpen = $activeFilterCount > 0 || $activeSortCount > 0 || request()->has('columns');
@endphp

<div class="card mb-3">
    <div class="card-body">
        <form method="get" id="data-view-filter-form">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label">Saved View</label>
                    <select class="form-select" name="view_id" onchange="this.form.submit()">
                        <option value="__standard__" @selected(request('view_id')==='__standard__')>Standard</option>
                        @foreach($state['views'] as $view)
                            <option value="{{ $view->id }}" @selected($activeView?->id===$view->id)>{{ $view->scope==='company'?'Company':'My' }} · {{ $view->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if($showQuickSearch)
                    <div class="col-lg-3">
                        <label class="form-label">Quick Search</label>
                        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Code / name">
                    </div>
                @endif

                <div class="col-lg-2">
                    <label class="form-label">Rows</label>
                    <select class="form-select" name="page_size">
                        @foreach([25,50,100,200] as $n)
                            <option value="{{ $n }}" @selected($state['pageSize']===$n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-lg-2">
                    <label class="form-label">Filter Mode</label>
                    <select class="form-select" name="filter_mode">
                        <option value="AND" @selected($state['mode']==='AND')>ALL (AND)</option>
                        <option value="OR" @selected($state['mode']==='OR')>ANY (OR)</option>
                    </select>
                </div>

                <div class="col d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary">Apply</button>
                    <a class="btn btn-outline-secondary" href="{{ $resetUrl }}">Reset</a>
                    <button
                        class="btn btn-outline-primary"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#{{ $panelId }}"
                        aria-expanded="{{ $advancedOpen ? 'true' : 'false' }}"
                        aria-controls="{{ $panelId }}">
                        Filter & Columns
                        @if(($activeFilterCount + $activeSortCount) > 0)
                            <span class="badge text-bg-primary ms-1">{{ $activeFilterCount + $activeSortCount }}</span>
                        @endif
                    </button>
                </div>
            </div>

            <div class="collapse {{ $advancedOpen ? 'show' : '' }} mt-3" id="{{ $panelId }}">
                <div class="border rounded-3 p-3 bg-body-tertiary">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold">Advanced data view</div>
                            <div class="small text-muted">Open only when you need column, filter, or sorting settings.</div>
                        </div>
                        @if($activeFilterCount > 0)
                            <span class="badge text-bg-secondary">{{ $activeFilterCount }} active filter{{ $activeFilterCount > 1 ? 's' : '' }}</span>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-xl-4">
                            <div class="border rounded p-3 h-100 bg-body">
                                <div class="fw-semibold mb-2">Columns</div>
                                <div class="small text-muted mb-2">Show/hide and move columns to define display order.</div>
                                <div id="column-list">
                                    @php $orderedColumns = array_values(array_unique(array_merge($state['columns'],array_keys($dataViewFields)))); @endphp
                                    @foreach($orderedColumns as $key)
                                        @php $field = $dataViewFields[$key]; @endphp
                                        <div class="d-flex align-items-center gap-2 py-1 column-row">
                                            <input class="form-check-input" type="checkbox" name="columns[]" value="{{ $key }}" @checked(in_array($key,$state['columns'],true))>
                                            <span class="flex-grow-1">{{ $field['label'] }}</span>
                                            <button class="btn btn-sm btn-light move-up" type="button">↑</button>
                                            <button class="btn btn-sm btn-light move-down" type="button">↓</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-5">
                            <div class="border rounded p-3 h-100 bg-body">
                                <div class="fw-semibold mb-2">Filters</div>
                                <div id="filters">
                                    @foreach($state['filters'] as $i=>$filter)
                                        <div class="row g-2 mb-2 filter-row">
                                            <div class="col-md-3">
                                                <select class="form-select field-select" name="filters[{{ $i }}][field]">
                                                    @foreach($dataViewFields as $key=>$field)
                                                        <option value="{{ $key }}" @selected(($filter['field']??'')===$key) data-type="{{ $field['type']??'text' }}">{{ $field['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <select class="form-select operator" name="filters[{{ $i }}][operator]">
                                                    @foreach(['contains'=>'contains','equals'=>'=','not_equals'=>'≠','starts_with'=>'starts with','ends_with'=>'ends with','not_contains'=>'not contains','gt'=>'>','gte'=>'≥','lt'=>'<','lte'=>'≤','between'=>'between','on'=>'on date','before'=>'before','after'=>'after'] as $op=>$label)
                                                        <option value="{{ $op }}" @selected(($filter['operator']??'contains')===$op)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3"><input class="form-control value" name="filters[{{ $i }}][value]" value="{{ $filter['value']??'' }}" placeholder="Value"></div>
                                            <div class="col-md-2"><input class="form-control value-to" name="filters[{{ $i }}][value_to]" value="{{ $filter['value_to']??'' }}" placeholder="To"></div>
                                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-filter">×</button></div>
                                        </div>
                                    @endforeach
                                </div>
                                <button class="btn btn-sm btn-outline-primary" type="button" id="add-filter">+ Add Filter</button>
                            </div>
                        </div>

                        <div class="col-xl-3">
                            <div class="border rounded p-3 h-100 bg-body">
                                <div class="fw-semibold mb-2">Sorting</div>
                                <div id="sorts">
                                    @foreach($state['sort'] as $i=>$sort)
                                        <div class="row g-2 mb-2 sort-row">
                                            <div class="col-7">
                                                <select class="form-select sort-field" name="sort[{{ $i }}][field]">
                                                    @foreach($dataViewFields as $key=>$field)
                                                        <option value="{{ $key }}" @selected(($sort['field']??'')===$key)>{{ $field['label'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <select class="form-select sort-direction" name="sort[{{ $i }}][direction]">
                                                    <option value="asc" @selected(($sort['direction']??'asc')==='asc')>ASC</option>
                                                    <option value="desc" @selected(($sort['direction']??'')==='desc')>DESC</option>
                                                </select>
                                            </div>
                                            <div class="col-1"><button type="button" class="btn btn-outline-danger remove-sort">×</button></div>
                                        </div>
                                    @endforeach
                                </div>
                                <button class="btn btn-sm btn-outline-primary" type="button" id="add-sort">+ Add Sort</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="d-flex gap-2 mb-3 flex-wrap align-items-center">
    <form method="post" action="{{ route('data-views.store') }}" class="d-flex gap-2 align-items-center data-view-payload-form">
        @csrf
        <input type="hidden" name="module_key" value="{{ $moduleKey }}">
        <input class="form-control" name="name" placeholder="New view name" required>
        <select class="form-select" name="scope">
            <option value="personal">Personal View</option>
            @if($canCompanyView)<option value="company">Company View</option>@endif
        </select>
        <label class="small text-nowrap"><input type="checkbox" name="make_default" value="1"> My default</label>
        <button class="btn btn-outline-primary">Save As</button>
    </form>

    @if($activeView)
        <form method="post" action="{{ route('data-views.update',$activeView) }}" class="d-flex gap-2 align-items-center data-view-payload-form">
            @csrf @method('PUT')
            <input class="form-control" name="name" value="{{ $activeView->name }}" required>
            <button class="btn btn-outline-primary">Update View</button>
        </form>
        <form method="post" action="{{ route('data-views.default',$activeView) }}">
            @csrf
            <button class="btn btn-outline-secondary">Set My Default</button>
        </form>
        <form method="post" action="{{ route('data-views.destroy',$activeView) }}" onsubmit="return confirm('Delete this saved view?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete View</button>
        </form>
    @endif
</div>

<template id="filter-template">
    <div class="row g-2 mb-2 filter-row">
        <div class="col-md-3"><select class="form-select field-select">@foreach($dataViewFields as $key=>$field)<option value="{{ $key }}" data-type="{{ $field['type']??'text' }}">{{ $field['label'] }}</option>@endforeach</select></div>
        <div class="col-md-3"><select class="form-select operator">@foreach(['contains'=>'contains','equals'=>'=','not_equals'=>'≠','starts_with'=>'starts with','ends_with'=>'ends with','not_contains'=>'not contains','gt'=>'>','gte'=>'≥','lt'=>'<','lte'=>'≤','between'=>'between','on'=>'on date','before'=>'before','after'=>'after'] as $op=>$label)<option value="{{ $op }}">{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><input class="form-control value" placeholder="Value"></div>
        <div class="col-md-2"><input class="form-control value-to" placeholder="To"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger remove-filter">×</button></div>
    </div>
</template>

<template id="sort-template">
    <div class="row g-2 mb-2 sort-row">
        <div class="col-7"><select class="form-select sort-field">@foreach($dataViewFields as $key=>$field)<option value="{{ $key }}">{{ $field['label'] }}</option>@endforeach</select></div>
        <div class="col-4"><select class="form-select sort-direction"><option value="asc">ASC</option><option value="desc">DESC</option></select></div>
        <div class="col-1"><button type="button" class="btn btn-outline-danger remove-sort">×</button></div>
    </div>
</template>

@push('scripts')
<script>
(()=>{
 const main=document.getElementById('data-view-filter-form'), columns=document.getElementById('column-list'), filters=document.getElementById('filters'), sorts=document.getElementById('sorts');
 columns?.addEventListener('click',e=>{const row=e.target.closest('.column-row');if(!row)return;if(e.target.classList.contains('move-up')&&row.previousElementSibling)columns.insertBefore(row,row.previousElementSibling);if(e.target.classList.contains('move-down')&&row.nextElementSibling)columns.insertBefore(row.nextElementSibling,row)});
 const renumberFilters=()=>[...filters.querySelectorAll('.filter-row')].forEach((row,i)=>{row.querySelector('.field-select').name=`filters[${i}][field]`;row.querySelector('.operator').name=`filters[${i}][operator]`;row.querySelector('.value').name=`filters[${i}][value]`;row.querySelector('.value-to').name=`filters[${i}][value_to]`;});
 const renumberSorts=()=>[...sorts.querySelectorAll('.sort-row')].forEach((row,i)=>{row.querySelector('.sort-field').name=`sort[${i}][field]`;row.querySelector('.sort-direction').name=`sort[${i}][direction]`;});
 document.getElementById('add-filter')?.addEventListener('click',()=>{filters.append(document.getElementById('filter-template').content.cloneNode(true));renumberFilters()});
 filters?.addEventListener('click',e=>{if(e.target.classList.contains('remove-filter')){e.target.closest('.filter-row').remove();renumberFilters()}});
 document.getElementById('add-sort')?.addEventListener('click',()=>{sorts.append(document.getElementById('sort-template').content.cloneNode(true));renumberSorts()});
 sorts?.addEventListener('click',e=>{if(e.target.classList.contains('remove-sort')){e.target.closest('.sort-row').remove();renumberSorts()}});
 const syncPayload=form=>{form.querySelectorAll('[data-synced-view]').forEach(n=>n.remove());const data=new FormData(main);for(const [name,value] of data.entries()){if(!(name==='filter_mode'||name==='page_size'||name.startsWith('columns[')||name==='columns[]'||name.startsWith('filters[')||name.startsWith('sort[')))continue;const input=document.createElement('input');input.type='hidden';input.name=name;input.value=value;input.dataset.syncedView='1';form.appendChild(input)}};
 document.querySelectorAll('.data-view-payload-form').forEach(form=>form.addEventListener('submit',()=>syncPayload(form)));
})();
</script>
@endpush
