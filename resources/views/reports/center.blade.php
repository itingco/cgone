@extends('layouts.app')
@section('title','Report Center')
@section('content')
<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
    <div>
        <div class="text-muted small text-uppercase fw-bold" style="letter-spacing:.08em">Reporting</div>
        <h3 class="mb-1">Report Center</h3>
        <div class="text-muted">Standard and shared reports available for your role.</div>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-start">
        @if($canBuild)<a class="btn btn-primary" href="{{ route('reports.builder.index') }}">+ Custom Report</a>@endif
        @if($canSql)<a class="btn btn-outline-dark" href="{{ route('reports.sql.index') }}">Advanced SQL</a>@endif
        @if($canAdmin)<a class="btn btn-outline-secondary" href="{{ route('reports.admin.performance') }}">Report Performance</a>@endif
        <form method="get" class="d-flex gap-2" style="min-width:min(100%,420px)">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Search report...">
        <button class="btn btn-outline-secondary">Search</button>
        @if($search)<a class="btn btn-light" href="{{ route('reports.center') }}">Clear</a>@endif
        </form>
    </div>
</div>

@if($favorites->isNotEmpty())
<div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2"><span>★</span><h5 class="mb-0">Favorites</h5></div>
    <div class="row g-3">
        @foreach($favorites as $report)
            <div class="col-md-6 col-xl-4">
                <div class="card h-100"><div class="card-body d-flex justify-content-between gap-3">
                    <div><div class="text-muted small">{{ $report->category }}</div><a class="fw-bold text-decoration-none" href="{{ route('reports.run',$report) }}">{{ $report->name }}</a><div class="small text-muted mt-1">{{ $report->code }}</div></div>
                    <form method="post" action="{{ route('reports.favorites.destroy',$report) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-light" title="Remove favorite">★</button></form>
                </div></div>
            </div>
        @endforeach
    </div>
</div>
@endif

@if($recent->isNotEmpty())
<div class="mb-4">
    <h5 class="mb-2">Recently Used</h5>
    <div class="d-flex flex-wrap gap-2">
        @foreach($recent as $report)
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('reports.run',$report) }}">{{ $report->name }}</a>
        @endforeach
    </div>
</div>
@endif

@forelse($categories as $category => $reports)
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">{{ $category }}</h5><span class="text-muted small">{{ $reports->count() }} report</span>
    </div>
    <div class="card"><div class="list-group list-group-flush">
        @foreach($reports as $report)
            <div class="list-group-item py-3 d-flex align-items-center justify-content-between gap-3">
                <div>
                    <a class="fw-semibold text-decoration-none" href="{{ route('reports.run',$report) }}">{{ $report->name }}</a>
                    <div class="small text-muted">{{ $report->code }} · {{ $report->report_type }}</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if(in_array($report->id,$favoriteIds))
                        <form method="post" action="{{ route('reports.favorites.destroy',$report) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-light" title="Remove favorite">★</button></form>
                    @else
                        <form method="post" action="{{ route('reports.favorites.store',$report) }}">@csrf<button class="btn btn-sm btn-light" title="Add favorite">☆</button></form>
                    @endif
                    <a class="btn btn-sm btn-primary" href="{{ route('reports.run',$report) }}">Open</a>
                </div>
            </div>
        @endforeach
    </div></div>
</div>
@empty
<div class="card"><div class="card-body py-5 text-center text-muted">No reports are available for your current access.</div></div>
@endforelse
@endsection
