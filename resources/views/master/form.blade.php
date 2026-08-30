@extends('layouts.app')
@section('title',($isEdit?'Edit ':'New ').$title)
@section('content')
@php
    $groups = $fieldGroups ?? [];
    if (empty($groups)) {
        $groups = ['main'=>['label'=>'General','fields'=>array_keys($fields)]];
    }
    $firstError = $errors->keys()[0] ?? null;
    $activeGroup = array_key_first($groups);
    if ($firstError) {
        foreach ($groups as $key=>$group) {
            if (in_array($firstError,$group['fields']??[],true)) { $activeGroup=$key; break; }
        }
    }
@endphp
<style>
.master-shell{max-width:1180px;margin:0 auto}.master-page-head{display:flex;justify-content:space-between;gap:1rem;align-items:flex-start;margin-bottom:1rem;flex-wrap:wrap}.master-page-head h4{font-weight:800;letter-spacing:-.02em}.master-card{border:1px solid #e8edf4!important;overflow:hidden}.master-tab-scroll{display:flex;gap:.35rem;overflow-x:auto;padding:.7rem .75rem;border-bottom:1px solid #e8edf4;background:#fbfcfe;scrollbar-width:thin}.master-tab-btn{white-space:nowrap;border:0;background:transparent;color:#66758a;font-weight:700;font-size:.86rem;padding:.65rem .85rem;border-radius:9px}.master-tab-btn:hover{background:#eef3f8;color:#26384d}.master-tab-btn.active{background:#16263b;color:#fff}.master-tab-panel{display:none}.master-tab-panel.active{display:block}.master-form-panel{padding:1.25rem}.master-field{background:#fff}.master-field .form-label{font-size:.78rem;font-weight:800;color:#53657a;margin-bottom:.4rem}.master-field .form-control,.master-field .form-select{border-color:#dce3ec;border-radius:9px;min-height:42px}.master-field textarea.form-control{min-height:100px}.master-field .form-control:focus,.master-field .form-select:focus{border-color:#6e8aad;box-shadow:0 0 0 .18rem rgba(69,103,145,.12)}.master-actions{display:flex;justify-content:flex-end;gap:.6rem;padding:1rem 1.25rem;border-top:1px solid #e8edf4;background:#fbfcfe;position:sticky;bottom:0;z-index:2}.required-dot{color:#d9534f}@media(max-width:767px){.master-shell{max-width:none}.master-form-panel{padding:1rem}.master-actions{justify-content:stretch}.master-actions .btn{flex:1}.master-tab-scroll{margin:0}.master-page-head .text-muted{font-size:.78rem}}
</style>
<div class="master-shell">
    <div class="master-page-head">
        <div>
            <h4 class="mb-1">{{ $isEdit ? 'Edit' : 'New' }} {{ rtrim($title,'s') }}</h4>
            <div class="text-muted small">Field dikelompokkan per kategori agar lebih mudah diperiksa dan diubah.</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route($routeBase.'.index') }}">Kembali ke daftar</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger py-2"><strong>Data belum dapat disimpan.</strong> Periksa field yang ditandai pada tab terkait.</div>
    @endif

    <div class="card master-card">
        <form method="post" action="{{ $isEdit?route($routeBase.'.update',$record):route($routeBase.'.store') }}">
            @csrf @if($isEdit)@method('PUT')@endif
            <div class="master-tab-scroll" role="tablist">
                @foreach($groups as $key=>$group)
                    @php $groupHasError=collect($group['fields']??[])->contains(fn($f)=>$errors->has($f)); @endphp
                    <button type="button" class="master-tab-btn {{ $key===$activeGroup?'active':'' }}" data-master-tab="{{ $key }}">
                        {{ $group['label'] ?? ucfirst($key) }} @if($groupHasError)<span class="text-danger">●</span>@endif
                    </button>
                @endforeach
            </div>

            @foreach($groups as $key=>$group)
                <div class="master-tab-panel {{ $key===$activeGroup?'active':'' }}" data-master-panel="{{ $key }}">
                    <div class="master-form-panel">
                        <div class="row g-3">
                            @foreach($group['fields']??[] as $name)
                                @continue(!isset($fields[$name]))
                                @php
                                    $field=$fields[$name]; $isWide=$field['type']==='textarea';
                                    $baseValue = $isEdit ? ($record->{$name} ?? null) : ($field['default'] ?? (($field['type']??null)==='number' ? 0 : null));
                                    if (($field['type']??null)==='date' && $baseValue) { try { $baseValue=\Carbon\Carbon::parse($baseValue)->format('Y-m-d'); } catch (\Throwable $e) {} }
                                    $inputValue = old($name,$baseValue);
                                @endphp
                                <div class="{{ $isWide?'col-12':'col-12 col-md-6' }} master-field">
                                    @if($field['type']==='checkbox')
                                        <input type="hidden" name="{{ $name }}" value="0">
                                        <div class="form-check form-switch mt-3 pt-2">
                                            <input class="form-check-input" type="checkbox" name="{{ $name }}" value="1" id="{{ $name }}" @checked((bool)$inputValue)>
                                            <label class="form-check-label fw-semibold" for="{{ $name }}">{{ $field['label'] }}</label>
                                        </div>
                                    @else
                                        <label class="form-label" for="{{ $name }}">{{ $field['label'] }} @if(!($field['nullable']??false))<span class="required-dot">*</span>@endif</label>
                                        @if($field['type']==='select')
                                            <select id="{{ $name }}" class="form-select @error($name) is-invalid @enderror" name="{{ $name }}" @if(!($field['nullable']??false)) required @endif>
                                                @if($field['nullable']??false)<option value="">- Pilih -</option>@endif
                                                @foreach($options[$field['options']]??[] as $value=>$label)
                                                    <option value="{{ $value }}" @selected((string)$inputValue===(string)$value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        @elseif($field['type']==='textarea')
                                            <textarea id="{{ $name }}" class="form-control @error($name) is-invalid @enderror" name="{{ $name }}" rows="4" @if(!($field['nullable']??false)) required @endif>{{ $inputValue }}</textarea>
                                        @else
                                            <input id="{{ $name }}" class="form-control @error($name) is-invalid @enderror" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $inputValue }}" @if(isset($field['step'])) step="{{ $field['step'] }}" @endif @if(!($field['nullable']??false)) required @endif>
                                        @endif
                                        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="master-actions">
                <a class="btn btn-outline-secondary" href="{{ $isEdit?route($routeBase.'.show',$record):route($routeBase.'.index') }}">Batal</a>
                <button class="btn btn-primary px-4">Simpan</button>
            </div>
        </form>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-master-tab]').forEach(btn=>btn.addEventListener('click',()=>{
    document.querySelectorAll('[data-master-tab]').forEach(x=>x.classList.remove('active'));
    document.querySelectorAll('[data-master-panel]').forEach(x=>x.classList.remove('active'));
    btn.classList.add('active');
    document.querySelector(`[data-master-panel="${btn.dataset.masterTab}"]`)?.classList.add('active');
  }));
});
</script>
@endsection
