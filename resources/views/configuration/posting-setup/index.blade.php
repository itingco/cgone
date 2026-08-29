@extends('layouts.app')
@section('title','Posting Setup')
@section('content')
@php
    $accountOptions = $accounts->mapWithKeys(fn ($a) => [$a->id => $a->code.' - '.$a->name]);
    $sections = [
        ['inventory','Inventory Posting Groups',$inventoryGroups,[['inventory_account_id','Inventory Account'],['cogs_account_id','COGS Account'],['adjustment_account_id','Adjustment Account']]],
        ['product','General Product Posting Groups',$productGroups,[['sales_account_id','Sales Account'],['purchase_account_id','Purchase Account']]],
        ['customer','Customer Posting Groups',$customerGroups,[['receivable_account_id','AR Account']]],
        ['vendor','Vendor Posting Groups',$vendorGroups,[['payable_account_id','AP Account']]],
        ['tax','Tax Posting Groups',$taxGroups,[['output_tax_account_id','Output VAT Account'],['input_tax_account_id','Input VAT Account']]],
    ];
@endphp
<div class="mb-3">
    <h4 class="mb-1">Posting Setup</h4>
    <div class="text-muted">Map operational groups to GL accounts. Transaction users do not select GL accounts manually.</div>
</div>
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white"><strong>Default / Inventory Control</strong></div>
            <div class="card-body">
                <form method="post" action="{{ route('config.posting-setup.save') }}">
                    @csrf
                    <input type="hidden" name="type" value="default">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">GRNI Account</label>
                            <select name="grni_account_id" class="form-select" required>
                                <option value="">Select...</option>
                                @foreach($accountOptions as $id => $label)
                                    <option value="{{ $id }}" @selected(optional($defaultSetup)->grni_account_id == $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Goods Received Not Invoiced.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inventory In Transit Account</label>
                            <select name="inventory_in_transit_account_id" class="form-select" required>
                                <option value="">Select...</option>
                                @foreach($accountOptions as $id => $label)
                                    <option value="{{ $id }}" @selected(optional($defaultSetup)->inventory_in_transit_account_id == $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Used between Goods Transfer SHIP and RECEIVE.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inventory Adjustment Account</label>
                            <select name="inventory_adjustment_account_id" class="form-select">
                                <option value="">-</option>
                                @foreach($accountOptions as $id => $label)
                                    <option value="{{ $id }}" @selected(optional($defaultSetup)->inventory_adjustment_account_id == $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3">Save Default</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white"><strong>Accounting Flow</strong></div>
            <div class="card-body small">
                <div class="mb-2"><strong>Shipment:</strong> Dr COGS / Cr Inventory</div>
                <div class="mb-2"><strong>Receipt:</strong> Dr Inventory / Cr GRNI</div>
                <div class="mb-2"><strong>Transfer Ship:</strong> Dr Inventory In Transit / Cr Inventory Source</div>
                <div class="mb-2"><strong>Transfer Receive:</strong> Dr Inventory Destination / Cr Inventory In Transit</div>
                <div class="mb-2"><strong>Sales Invoice:</strong> Dr AR / Cr Sales / Cr Output VAT</div>
                <div><strong>Purchase Invoice:</strong> Dr GRNI or Expense + Input VAT / Cr AP</div>
            </div>
        </div>
    </div>
</div>
@foreach($sections as [$type,$label,$rows,$fields])
    @php
        $editRow = $editingType === $type ? $editing : null;
    @endphp
    <div class="card mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <strong>{{ $editRow ? 'Edit '.$label : $label }}</strong>
            <span class="badge text-bg-light">{{ $rows->count() }} groups</span>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('config.posting-setup.save') }}" class="row g-2 align-items-end">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="id" value="{{ $editRow?->id }}">
                <div class="col-md-2"><label class="form-label">Code</label><input class="form-control" name="code" value="{{ old('code',$editRow?->code) }}" required></div>
                <div class="col-md-3"><label class="form-label">Name</label><input class="form-control" name="name" value="{{ old('name',$editRow?->name) }}" required></div>
                @if($type === 'tax')
                    <div class="col-md-1"><label class="form-label">Rate %</label><input class="form-control" type="number" step="0.0001" name="rate" value="{{ old('rate',$editRow?->rate ?? 0) }}"></div>
                @endif
                @foreach($fields as [$name,$fieldLabel])
                    <div class="col">
                        <label class="form-label">{{ $fieldLabel }}</label>
                        <select class="form-select" name="{{ $name }}" @if(!str_contains($name,'adjustment') && !str_contains($name,'tax_account')) required @endif>
                            <option value="">-</option>
                            @foreach($accountOptions as $id => $accountLabel)
                                <option value="{{ $id }}" @selected((string)old($name,$editRow?->{$name}) === (string)$id)>{{ $accountLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                @endforeach
                <div class="col-auto"><button class="btn btn-primary">{{ $editRow ? 'Update' : 'Add' }}</button>@if($editRow)<a class="btn btn-outline-secondary" href="{{ route('config.posting-setup.index') }}">Cancel</a>@endif</div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Code</th><th>Name</th>@if($type==='tax')<th>Rate</th>@endif @foreach($fields as $f)<th>{{ $f[1] }}</th>@endforeach<th></th></tr></thead>
                <tbody>
                @forelse($rows as $row)
                    <tr><td>{{ $row->code }}</td><td>{{ $row->name }}</td>@if($type==='tax')<td>{{ $row->rate }}%</td>@endif @foreach($fields as [$name,$fieldLabel])<td>{{ $accountOptions[$row->{$name}] ?? '-' }}</td>@endforeach<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('config.posting-setup.index',['edit_type'=>$type,'edit_id'=>$row->id]) }}">Edit</a></td></tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted">No groups configured.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
