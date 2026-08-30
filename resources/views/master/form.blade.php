@extends('layouts.app')
@section('title',($isEdit?'Edit ':'New ').$title)
@section('content')
@php
    $tabs = collect($fields)->groupBy(fn($field) => $field['tab'] ?? 'General');
    $errorFields = array_keys($errors->toArray());
    $activeTab = $tabs->keys()->first();
    foreach($tabs as $tabName => $tabFields){
        if(collect($tabFields)->keys()->intersect($errorFields)->isNotEmpty()){$activeTab=$tabName;break;}
    }
@endphp
<div class="card"><div class="card-body">
    <form method="post" action="{{ $isEdit?route($routeBase.'.update',$record):route($routeBase.'.store') }}">
        @csrf @if($isEdit)@method('PUT')@endif
        @if($tabs->count()>1)
            <ul class="nav nav-tabs mb-4" role="tablist">
                @foreach($tabs as $tabName=>$tabFields)
                    @php $tabId='tab-'.\Illuminate\Support\Str::slug($tabName); @endphp
                    <li class="nav-item" role="presentation"><button class="nav-link @if($activeTab===$tabName) active @endif" data-bs-toggle="tab" data-bs-target="#{{ $tabId }}" type="button">{{ $tabName }}</button></li>
                @endforeach
            </ul>
        @endif
        <div class="tab-content">
            @foreach($tabs as $tabName=>$tabFields)
                @php $tabId='tab-'.\Illuminate\Support\Str::slug($tabName); @endphp
                <div class="tab-pane fade @if($activeTab===$tabName) show active @endif" id="{{ $tabId }}">
                    <div class="row g-3">
                        @foreach($tabFields as $name=>$field)
                            @php $baseValue = $record->exists ? $record->{$name} : ($field['default'] ?? ($field['type']==='number' ? 0 : null)); @endphp
                            <div class="{{ $field['type']==='textarea'?'col-12':'col-md-6' }}">
                                @if($field['type']==='checkbox')
                                    <input type="hidden" name="{{ $name }}" value="0">
                                    <div class="form-check mt-4"><input class="form-check-input @error($name) is-invalid @enderror" type="checkbox" name="{{ $name }}" value="1" id="{{ $name }}" @checked((bool)old($name,$baseValue))><label class="form-check-label" for="{{ $name }}">{{ $field['label'] }}</label>@error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                                @else
                                    <label class="form-label">{{ $field['label'] }}</label>
                                    @if($field['type']==='select')
                                        <select class="form-select @error($name) is-invalid @enderror" name="{{ $name }}" @if(!($field['nullable']??false)) required @endif>
                                            @if($field['nullable']??false)<option value="">-</option>@endif
                                            @foreach($options[$field['options']]??[] as $value=>$label)<option value="{{ $value }}" @selected((string)old($name,$baseValue)===(string)$value)>{{ $label }}</option>@endforeach
                                        </select>
                                    @elseif($field['type']==='textarea')
                                        <textarea class="form-control @error($name) is-invalid @enderror" name="{{ $name }}" rows="4">{{ old($name,$baseValue) }}</textarea>
                                    @else
                                        <input class="form-control @error($name) is-invalid @enderror" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ old($name,$baseValue) }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif @if(!($field['nullable']??false)) required @endif>
                                    @endif
                                    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">Save</button><a class="btn btn-outline-secondary" href="{{ route($routeBase.'.index') }}">Cancel</a></div>
    </form>
</div></div>
@endsection
