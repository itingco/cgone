@extends('layouts.app')
@section('title',$title.' Detail')
@section('content')
@php $tabs=collect($fields)->groupBy(fn($field)=>$field['tab']??'General'); @endphp
<div class="card"><div class="card-body">
    @if($tabs->count()>1)
        <ul class="nav nav-tabs mb-4" role="tablist">@foreach($tabs as $tabName=>$tabFields)@php $tabId='detail-'.\Illuminate\Support\Str::slug($tabName); @endphp<li class="nav-item"><button class="nav-link @if($loop->first) active @endif" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" type="button">{{ $tabName }}</button></li>@endforeach</ul>
    @endif
    <div class="tab-content">
        @foreach($tabs as $tabName=>$tabFields)
            @php $tabId='detail-'.\Illuminate\Support\Str::slug($tabName); @endphp
            <div class="tab-pane fade @if($loop->first) show active @endif" id="{{ $tabId }}"><dl class="row mb-0">
                @foreach($tabFields as $name=>$field)
                    <dt class="col-md-4 col-lg-3 py-2 border-bottom">{{ $field['label'] }}</dt><dd class="col-md-8 col-lg-9 py-2 border-bottom mb-0">
                    @if($field['type']==='checkbox')<span class="badge {{ $record->{$name}?'text-bg-success':'text-bg-light border' }}">{{ $record->{$name}?'Yes':'No' }}</span>
                    @elseif($field['type']==='select'){{ ($options[$field['options']]??[])[$record->{$name}] ?? $record->{$name} ?? '-' }}
                    @else{!! $field['type']==='textarea' ? nl2br(e($record->{$name} ?? '-')) : e($record->{$name} ?? '-') !!}@endif
                    </dd>
                @endforeach
            </dl></div>
        @endforeach
    </div>
    <div class="d-flex gap-2 mt-4"><a class="btn btn-outline-secondary" href="{{ route($routeBase.'.index') }}">Back</a>@if(app(\App\Services\Security\MenuAuthorizationService::class)->allows(auth()->user(),str_replace('_','-',$routeBase),'edit'))<a class="btn btn-primary" href="{{ route($routeBase.'.edit',$record) }}">Edit</a>@endif<form method="post" action="{{ route($routeBase.'.status',$record) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $record->is_active?0:1 }}"><button class="btn {{ $record->is_active?'btn-outline-danger':'btn-outline-success' }}">{{ $record->is_active?'Deactivate':'Activate' }}</button></form></div>
</div></div>
@endsection
