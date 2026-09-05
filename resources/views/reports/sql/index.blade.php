@extends('layouts.app')
@section('title','Advanced SQL Reports')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div><div class="text-muted small text-uppercase fw-bold" style="letter-spacing:.08em">Reporting · IT/Admin</div><h3 class="mb-1">Advanced SQL Reports</h3><div class="text-muted">Read-only SQL reports. Execution is protected by validation, bound parameters, row limits, timeout, and PostgreSQL READ ONLY transactions.</div></div>
    <div class="d-flex gap-2">
        <a class="btn btn-light" href="{{ route('reports.center') }}">Report Center</a>
        @if($canCreate)
            <a class="btn btn-primary" href="{{ route('reports.sql.create') }}">+ New SQL Report</a>
        @endif
    </div>
</div>
<div class="card"><div class="list-group list-group-flush">
@forelse($reports as $report)
<div class="list-group-item py-3 d-flex justify-content-between gap-3 align-items-center">
    <div><a class="fw-semibold text-decoration-none" href="{{ route('reports.run',$report) }}">{{ $report->name }}</a><div class="small text-muted">{{ $report->code }} · {{ $report->category }} · {{ $report->visibility }}</div></div>
    <div class="d-flex gap-2">
        @if(in_array($report->id,$editIds,true))<a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.sql.edit',$report) }}">Edit</a>@endif
        <a class="btn btn-sm btn-primary" href="{{ route('reports.run',$report) }}">Run</a>
    </div>
</div>
@empty
<div class="p-5 text-center text-muted">No Advanced SQL Reports yet.</div>
@endforelse
</div></div>
@endsection
