@extends('layouts.app')
@section('title','My Profile')
@section('content')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-circle bg-dark text-white d-grid" style="width:54px;height:54px;place-items:center;font-weight:800">{{ strtoupper(substr($user->name,0,1)) }}</div>
                <div><h5 class="mb-0">{{ $user->name }}</h5><div class="text-muted small">{{ $user->email }}</div></div>
            </div>
            <form method="post" action="{{ route('profile.update') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-md-6"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name',$user->name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" value="{{ $user->email }}" readonly><div class="form-text">Email adalah identitas lintas database dan tidak diubah dari profile.</div></div>
                <div class="col-12"><button class="btn btn-primary">Save Profile</button></div>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <h5>Change Password</h5><div class="text-muted small mb-3">Password akan disinkronkan ke seluruh database terdaftar yang memiliki user dengan email yang sama.</div>
            <form method="post" action="{{ route('profile.password') }}" class="row g-3">
                @csrf @method('PUT')
                <div class="col-12"><label class="form-label">Current Password</label><input class="form-control" type="password" name="current_password" required autocomplete="current-password"></div>
                <div class="col-12"><label class="form-label">New Password</label><input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
                <div class="col-12"><label class="form-label">Confirm New Password</label><input class="form-control" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
                <div class="col-12"><button class="btn btn-outline-primary">Change Password</button></div>
            </form>
        </div></div>
        <div class="card mt-4"><div class="card-body d-flex align-items-center justify-content-between gap-3"><div><div class="fw-semibold">My Documents</div><div class="small text-muted">Posted documents created by this user.</div></div><a href="{{ route('profile.documents') }}" class="btn btn-outline-secondary">Open</a></div></div>
    </div>
</div>
@endsection
