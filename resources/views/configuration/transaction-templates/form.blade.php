@extends('layouts.app')
@section('title',$template->exists?'Edit Transaction Template':'New Transaction Template')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
<div><h4 class="mb-1">{{ $template->exists?'Edit':'New' }} Transaction Template</h4><div class="text-muted small">Template only supplies defaults. Posting accounts still come from Posting Groups / Posting Setup.</div></div>
<a class="btn btn-outline-secondary" href="{{ route('transaction-templates.index') }}">Back</a>
</div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
<form method="post" action="{{ $template->exists?route('transaction-templates.update',$template):route('transaction-templates.store') }}">
@csrf @if($template->exists)@method('PUT')@endif
<div class="row g-3">
<div class="col-lg-5"><div class="card h-100"><div class="card-header bg-white fw-semibold">Template</div><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label">Code</label><input class="form-control" name="code" value="{{ old('code',$template->code) }}" required></div>
<div class="col-md-8"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name',$template->name) }}" required></div>
<div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2">{{ old('description',$template->description) }}</textarea></div>
<div class="col-12"><label class="form-label d-block">Applies To</label><div class="row g-2">@foreach($documentTypes as $code=>$label)<div class="col-6"><label class="border rounded p-2 w-100"><input class="form-check-input me-2" type="checkbox" name="document_types[]" value="{{ $code }}" @checked(in_array($code,$selectedTypes,true))>{{ $label }}</label></div>@endforeach</div></div>
<div class="col-12"><div class="form-check form-switch"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$template->exists?$template->is_active:true))><label class="form-check-label">Active</label></div></div>
</div></div></div>
<div class="col-lg-7"><div class="card"><div class="card-header bg-white fw-semibold">Default Values</div><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">Business Unit</label><select class="form-select" name="business_unit_id"><option value="">-</option>@foreach($businessUnits as $r)<option value="{{ $r->id }}" @selected((string)old('business_unit_id',$template->business_unit_id)===(string)$r->id)>{{ $r->code }} - {{ $r->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Location</label><select class="form-select" name="location_id"><option value="">-</option>@foreach($locations as $r)<option value="{{ $r->id }}" @selected((string)old('location_id',$template->location_id)===(string)$r->id)>{{ $r->code }} - {{ $r->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Bin / Sub Location</label><select class="form-select" name="bin_id"><option value="">-</option>@foreach($locations as $l)@foreach($l->bins as $b)<option value="{{ $b->id }}" @selected((string)old('bin_id',$template->bin_id)===(string)$b->id)>{{ $l->code }} / {{ $b->code }}</option>@endforeach @endforeach</select></div>
<div class="col-md-6"><label class="form-label">Price Level</label><select class="form-select" name="price_level_id"><option value="">-</option>@foreach($priceLevels as $r)<option value="{{ $r->id }}" @selected((string)old('price_level_id',$template->price_level_id)===(string)$r->id)>{{ $r->code }} - {{ $r->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Currency</label><input class="form-control" name="currency_code" value="{{ old('currency_code',$template->currency_code) }}" placeholder="IDR"></div>
<div class="col-md-4"><label class="form-label">Payment Term (Days)</label><input class="form-control" type="number" min="0" name="payment_term_days" value="{{ old('payment_term_days',$template->payment_term_days) }}"></div>
<div class="col-md-4"><label class="form-label">Tax Type / Posting Group</label><select class="form-select" name="tax_posting_group_id"><option value="">Item/Partner Default</option>@foreach($taxGroups as $r)<option value="{{ $r->id }}" @selected((string)old('tax_posting_group_id',$template->tax_posting_group_id)===(string)$r->id)>{{ $r->code }} - {{ $r->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Number Series</label><select class="form-select" name="number_series_code"><option value="">Document Type Default</option>@foreach($numberSeries as $r)<option value="{{ $r->code }}" @selected((string)old('number_series_code',$template->number_series_code)===(string)$r->code)>{{ $r->code }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Default Notes</label><textarea class="form-control" name="default_notes" rows="3">{{ old('default_notes',$template->default_notes) }}</textarea></div>
<div class="col-12"><div class="alert alert-light border mb-0 small"><strong>Accounting rule:</strong> Customer/Vendor/Item Posting Groups and Posting Setup are not overridden by this template.</div></div>
</div></div></div>
</div>
<div class="mt-3"><button class="btn btn-primary">Save Template</button></div>
</form>
@endsection
