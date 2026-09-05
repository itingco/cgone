<div class="card">
<div class="card-header bg-white d-flex justify-content-between"><strong>Preview</strong><span class="small text-muted">Maximum {{ config('reports.preview_row_limit',500) }} rows</span></div>
<div class="card-body">
@if(!empty($result->summary))
<div class="row g-2 mb-3">
    @foreach($result->summary as $label=>$value)
        <div class="col-md-3">
            <div class="border rounded p-2">
                <div class="small text-muted">{{ $label }}</div>
                <div class="fw-bold">{{ is_numeric($value)?number_format((float)$value,2,',','.'):$value }}</div>
            </div>
        </div>
    @endforeach
</div>
@endif
@include('reports.partials.table')
</div></div>
