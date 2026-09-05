@extends('layouts.app')
@section('title','Report Builder')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div><div class="text-muted small text-uppercase fw-bold">Reporting</div><h3 class="mb-1">Visual Report Builder</h3><div class="text-muted">Build a report from approved ERP data sources. No SQL or table names are exposed.</div></div>
    <a class="btn btn-light" href="{{ route('reports.center') }}">Report Center</a>
</div>

<div class="card mb-4">
    <div class="card-header bg-white"><strong>Choose Data Source</strong></div>
    <div class="card-body"><div class="row g-3">
        @foreach($datasources->groupBy('category') as $category=>$rows)
            <div class="col-lg-4">
                <div class="text-muted small fw-bold text-uppercase mb-2">{{ $category }}</div>
                <div class="d-grid gap-2">
                    @foreach($rows as $source)
                        <a class="btn btn-outline-primary text-start" href="{{ route('reports.builder.create',['datasource'=>$source->code]) }}">
                            <strong>{{ $source->name }}</strong><br><span class="small text-muted">{{ $source->code }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div></div>
</div>

<div class="card">
    <div class="card-header bg-white"><strong>My Custom Reports</strong></div>
    <div class="list-group list-group-flush">
        @forelse($mine as $report)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div><div class="fw-semibold">{{ $report->name }}</div><div class="small text-muted">{{ $report->category }} · {{ data_get($report->definition_json,'datasource') }} · {{ $report->visibility }}</div></div>
                <div class="d-flex gap-2"><a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.builder.edit',$report) }}">Edit</a><a class="btn btn-sm btn-primary" href="{{ route('reports.run',$report) }}">Open</a></div>
            </div>
        @empty
            <div class="list-group-item py-4 text-center text-muted">You have not created a custom report yet.</div>
        @endforelse
    </div>
</div>
@endsection
