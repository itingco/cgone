@extends('layouts.app')
@section('title','Change Password')
@section('content')
<div class="row justify-content-center"><div class="col-lg-6"><div class="card"><div class="card-body">
 <h5 class="mb-3">Change Password</h5>
 <form method="post" action="{{ route('profile.password.update') }}">@csrf @method('PUT')
  <div class="mb-3"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required autocomplete="current-password"></div>
  <div class="mb-3"><label class="form-label">New Password</label><input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password"></div>
  <div class="mb-3"><label class="form-label">Confirm New Password</label><input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password"></div>
  <button class="btn btn-primary">Update Password</button> <a class="btn btn-outline-secondary" href="{{ route('dashboard') }}">Cancel</a>
 </form>
</div></div></div></div>
@endsection
