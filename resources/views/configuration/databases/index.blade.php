@extends('layouts.app')
@section('title','Database Manager')
@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card"><div class="card-body">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="mb-1">PostgreSQL Server</h5>
                    <div class="text-muted small">Database baru memakai server, port, username dan password yang sama dari .env.</div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="badge text-bg-light border">Driver: {{ $connection['driver'] }}</span>
                    <span class="badge text-bg-light border">{{ $connection['host'] }}:{{ $connection['port'] }}</span>
                    <span class="badge text-bg-light border">User: {{ $connection['username'] }}</span>
                </div>
            </div>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h5>Create New Database</h5>
            <p class="text-muted small">Membuat database PostgreSQL baru, lalu otomatis menjalankan migration + seeder CGOne.</p>
            <form method="post" action="{{ route('database.manager.create') }}" class="row g-3">
                @csrf
                <div class="col-md-6"><label class="form-label">Database Name</label><input class="form-control" name="database" placeholder="cgone_company_b" required pattern="[A-Za-z][A-Za-z0-9_]{0,62}"></div>
                <div class="col-md-6"><label class="form-label">Display Label</label><input class="form-control" name="label" placeholder="Company B" required></div>
                <div class="col-12"><div class="alert alert-warning py-2 small mb-0">Create membutuhkan user PostgreSQL pada .env memiliki privilege <strong>CREATEDB</strong>.</div></div>
                <div class="col-12"><button class="btn btn-primary">Create & Initialize</button></div>
            </form>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100"><div class="card-body">
            <h5>Register Existing Database</h5>
            <p class="text-muted small">Untuk database yang sudah ada pada server yang sama. Sistem mengetes koneksi sebelum memasukkannya ke selector.</p>
            <form method="post" action="{{ route('database.manager.register') }}" class="row g-3">
                @csrf
                <div class="col-md-6"><label class="form-label">Database Name</label><input class="form-control" name="database" placeholder="existing_erp" required pattern="[A-Za-z][A-Za-z0-9_]{0,62}"></div>
                <div class="col-md-6"><label class="form-label">Display Label</label><input class="form-control" name="label" placeholder="Existing ERP" required></div>
                <div class="col-12 d-flex gap-2"><button class="btn btn-success">Test & Register</button></div>
            </form>
            <hr>
            <form method="post" action="{{ route('database.manager.test') }}" class="d-flex gap-2">
                @csrf
                <input class="form-control" name="database" placeholder="Database name to test" required pattern="[A-Za-z][A-Za-z0-9_]{0,62}">
                <button class="btn btn-outline-secondary text-nowrap">Test Only</button>
            </form>
        </div></div>
    </div>

    <div class="col-12">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-0">Registered Databases</h5><div class="small text-muted">Unregister hanya menghilangkan dari selector, tidak menghapus database fisik.</div></div></div>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead><tr><th>Label</th><th>Database</th><th>Status</th><th>Source</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                @foreach($databases as $database => $label)
                    <tr>
                        <td class="fw-semibold">{{ $label }}</td>
                        <td><code>{{ $database }}</code></td>
                        <td>
                            @if($database === $activeDatabase)<span class="badge text-bg-success">Active</span>
                            @elseif($database === $defaultDatabase)<span class="badge text-bg-primary">Default</span>
                            @else<span class="badge text-bg-light border">Available</span>@endif
                        </td>
                        <td>{{ array_key_exists($database,$runtimeDatabases) ? 'Database Manager' : '.env / default' }}</td>
                        <td class="text-end">
                            <form method="post" action="{{ route('database.manager.test') }}" class="d-inline">@csrf<input type="hidden" name="database" value="{{ $database }}"><button class="btn btn-sm btn-outline-secondary">Test</button></form>
                            @if(array_key_exists($database,$runtimeDatabases) && $database !== $defaultDatabase && $database !== $activeDatabase)
                                <form method="post" action="{{ route('database.manager.unregister',$database) }}" class="d-inline" onsubmit="return confirm('Unregister database ini dari selector? Database fisik tidak dihapus.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Unregister</button></form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
@endsection
