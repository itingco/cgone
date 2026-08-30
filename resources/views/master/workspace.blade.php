@extends('layouts.app')
@section('title',$title.' Detail')
@section('content')
@php
    $authz=app(\App\Services\Security\MenuAuthorizationService::class);
    $canEdit=$authz->allows(auth()->user(),$menuCode,'edit');
    $money=fn($v)=>number_format((float)$v,2,',','.');
    $qty=fn($v)=>number_format((float)$v,4,',','.');
    $date=fn($v,$fmt='d M Y')=>$v?\Carbon\Carbon::parse($v)->format($fmt):'-';
    $yesNo=fn($v)=>$v?'Yes':'No';
    $fieldValue=function($name) use($fields,$options,$record,$money){
        $field=$fields[$name]??null; if(!$field)return '-'; $raw=$record->{$name}??null;
        if(($field['type']??null)==='checkbox')return $raw?'Yes':'No';
        if(($field['type']??null)==='select'){ $list=$options[$field['options']]??[]; return $list[$raw]??$raw??'-'; }
        if(($field['type']??null)==='number' && $raw!==null)return $money($raw);
        if(($field['type']??null)==='date' && $raw)return \Carbon\Carbon::parse($raw)->format('d M Y');
        return ($raw===null||$raw==='')?'-':$raw;
    };
    $recordType = match($workspaceType){'item'=>'Item','customer'=>'Customer','vendor'=>'Supplier',default=>'Master'};
@endphp
<style>
:root{--erp-navy:#14243a;--erp-ink:#1e2f43;--erp-muted:#6b7c90;--erp-line:#e3eaf2;--erp-soft:#f7f9fc;--erp-accent:#ff9f1c;--erp-blue:#2f6fad;--erp-green:#16845b;--erp-red:#b84a4a}
.ws-wrap{max-width:1540px;margin:0 auto}.ws-recordbar{background:#fff;border:1px solid var(--erp-line);border-radius:14px;padding:1rem 1.1rem;box-shadow:0 .15rem .7rem rgba(24,39,60,.05);margin-bottom:.85rem}.ws-recordbar-main{display:grid;grid-template-columns:minmax(130px,200px) minmax(280px,1fr) auto;gap:1rem;align-items:end}.ws-kicker{font-size:.67rem;text-transform:uppercase;letter-spacing:.09em;font-weight:800;color:#7b8ba0}.ws-codebox,.ws-namebox{font-weight:800;color:var(--erp-ink);line-height:1.15}.ws-codebox{font-size:1rem}.ws-namebox{font-size:1.25rem}.ws-record-meta{display:flex;gap:.45rem;align-items:center;justify-content:flex-end;flex-wrap:wrap}.ws-pill{display:inline-flex;align-items:center;gap:.35rem;padding:.35rem .6rem;border-radius:999px;background:#eef3f8;color:#44576d;font-size:.74rem;font-weight:800}.ws-pill.live{background:#e7f7ef;color:#146b49}.ws-pill.off{background:#fbeaea;color:#963f3f}.ws-actions{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.85rem;border-top:1px solid #edf1f5;padding-top:.8rem}.ws-card{background:#fff;border:1px solid var(--erp-line);border-radius:14px;overflow:hidden;box-shadow:0 .15rem .7rem rgba(24,39,60,.04)}.ws-tabs{display:flex;gap:.1rem;overflow-x:auto;padding:.2rem .35rem 0;background:#fbfcfe;border-bottom:1px solid var(--erp-line);scrollbar-width:thin}.ws-tab{white-space:nowrap;border:0;border-bottom:3px solid transparent;background:transparent;padding:.8rem .75rem .65rem;color:#5f7084;font-size:.82rem;font-weight:750}.ws-tab:hover{color:var(--erp-ink);background:#f3f6fa}.ws-tab.active{color:var(--erp-ink);border-bottom-color:var(--erp-accent);background:#fff}.ws-panel{display:none}.ws-panel.active{display:block}.ws-panel-pad{padding:1rem}.ref-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:.85rem}.ref-section{grid-column:span 6;border:1px solid var(--erp-line);border-radius:11px;background:#fff;overflow:hidden}.ref-section.wide{grid-column:1/-1}.ref-section.third{grid-column:span 4}.ref-section-title{padding:.68rem .85rem;background:#f8fafc;border-bottom:1px solid var(--erp-line);font-size:.76rem;text-transform:uppercase;letter-spacing:.05em;color:#607187;font-weight:850}.ref-section-body{padding:.78rem .85rem}.info-grid{display:grid;grid-template-columns:minmax(130px,.7fr) minmax(0,1.5fr);column-gap:.75rem;row-gap:.12rem}.info-label,.info-value{padding:.35rem 0;border-bottom:1px dashed #edf1f5;min-width:0}.info-label{font-size:.75rem;color:#708096}.info-value{font-size:.82rem;color:#213448;font-weight:650;word-break:break-word}.switch-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.5rem}.flag-chip{border:1px solid var(--erp-line);border-radius:9px;padding:.55rem .65rem;display:flex;justify-content:space-between;gap:.5rem;font-size:.78rem;background:#fbfcfe}.flag-chip .state{font-weight:850}.flag-chip .state.on{color:var(--erp-green)}.flag-chip .state.off{color:#8a98a8}.ws-table-wrap{overflow:auto}.ws-table{min-width:780px;font-size:.78rem}.ws-table thead th{position:sticky;top:0;background:#f7f9fc;color:#5d6f84;font-size:.69rem;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid var(--erp-line);padding:.7rem .7rem;white-space:nowrap}.ws-table td{padding:.62rem .7rem;border-color:#edf1f5;vertical-align:middle}.ws-table .ws-num{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.ws-empty{text-align:center;color:#8492a3;padding:2.2rem!important}.ws-note{padding:.7rem .85rem;background:#fff8e9;border-bottom:1px solid #f4dfb2;color:#73551a;font-size:.76rem}.ws-kpi-row{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.7rem;padding:.85rem}.ws-kpi{border:1px solid var(--erp-line);border-radius:10px;padding:.75rem .85rem;background:#fbfcfe}.ws-kpi span{display:block;font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;color:#74859a;font-weight:800}.ws-kpi strong{display:block;margin-top:.2rem;color:#20344b;font-size:1.05rem}.ws-composite{display:grid;grid-template-columns:190px minmax(0,1fr);min-height:460px}.ws-subnav{border-right:1px solid var(--erp-line);background:#fafbfd;padding:.55rem}.ws-subtab{display:block;width:100%;text-align:left;border:0;background:transparent;border-left:3px solid transparent;color:#566a80;padding:.62rem .62rem;font-size:.76rem;font-weight:750;border-radius:0 7px 7px 0;margin-bottom:.1rem}.ws-subtab:hover{background:#f0f4f8}.ws-subtab.active{background:#fff;border-left-color:var(--erp-accent);color:#1d3249;box-shadow:0 1px 3px rgba(20,36,58,.05)}.ws-subcontent{min-width:0}.ws-subpanel{display:none}.ws-subpanel.active{display:block}.address-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.address-card{border:1px solid var(--erp-line);border-radius:10px;padding:.75rem;background:#fff}.address-type{font-size:.7rem;font-weight:850;color:#50677e;text-transform:uppercase;letter-spacing:.06em}.address-title{font-weight:800;color:#1f344a;margin:.15rem 0}.address-text{font-size:.78rem;color:#617389;white-space:pre-line}.spec-box{min-height:180px;border:1px solid var(--erp-line);border-radius:10px;padding:.85rem;background:#fbfcfe;white-space:pre-wrap;font-size:.82rem;color:#334a61}.ws-form-mini{border-top:1px solid var(--erp-line);padding:.85rem;background:#fbfcfe}.ws-audit-change{min-width:280px;font-size:.74rem}.status-open{background:#eef3f8;color:#4a6077}.status-released{background:#fff1d7;color:#8b5b00}.status-posted{background:#e5f6ed;color:#176c4a}.muted-box{padding:1.2rem;text-align:center;color:#7d8ca0;background:#fbfcfe;border:1px dashed #dce4ee;border-radius:10px}.form-control-sm,.form-select-sm{border-color:#dbe3ec}.table-summary{display:flex;justify-content:flex-end;gap:1.2rem;padding:.65rem .85rem;background:#fafbfd;border-top:1px solid var(--erp-line);font-size:.76rem}.table-summary strong{font-size:.86rem;color:#22374d}
@media(max-width:1100px){.ref-section.third{grid-column:span 6}.switch-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.ws-recordbar-main{grid-template-columns:160px 1fr}.ws-record-meta{grid-column:1/-1;justify-content:flex-start}.ws-kpi-row{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:767px){.ws-wrap{max-width:none}.ws-recordbar{padding:.85rem}.ws-recordbar-main{grid-template-columns:1fr}.ws-record-meta{grid-column:auto}.ws-namebox{font-size:1.05rem}.ws-actions .btn{flex:1}.ref-section,.ref-section.third{grid-column:1/-1}.info-grid{grid-template-columns:1fr}.info-label{padding-bottom:0;border-bottom:0}.info-value{padding-top:.12rem}.switch-grid{grid-template-columns:1fr}.ws-kpi-row{grid-template-columns:1fr 1fr}.ws-composite{grid-template-columns:1fr}.ws-subnav{display:flex;overflow-x:auto;border-right:0;border-bottom:1px solid var(--erp-line);padding:.35rem}.ws-subtab{width:auto;white-space:nowrap;border-left:0;border-bottom:3px solid transparent;border-radius:7px;padding:.55rem .65rem}.ws-subtab.active{border-left:0;border-bottom-color:var(--erp-accent)}.address-grid{grid-template-columns:1fr}.ws-panel-pad{padding:.75rem}.ws-table{min-width:700px}}
</style>
<!-- Compatibility vocabulary: Ledger History | Audit Log | Pending Invoices | Payment History | Valuation & COGS | Price Level History -->
<div class="ws-wrap">
    <div class="ws-recordbar">
        <div class="ws-recordbar-main">
            <div><div class="ws-kicker">{{ $recordType }} Code</div><div class="ws-codebox">{{ $record->code }}</div></div>
            <div><div class="ws-kicker">Name</div><div class="ws-namebox">{{ $record->name }}</div></div>
            <div class="ws-record-meta">
                @if($workspaceType==='item')<span class="ws-pill">{{ $record->class_code ?: 'No Class' }}</span><span class="ws-pill">{{ $record->item_type }}</span>@endif
                <span class="ws-pill {{ $record->is_active?'live':'off' }}">{{ $record->is_active?'Active':'Inactive' }}</span>
            </div>
        </div>
        <div class="ws-actions">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route($routeBase.'.index') }}">Back</a>
            @if($canEdit)<a class="btn btn-sm btn-primary" href="{{ route($routeBase.'.edit',$record) }}">Edit {{ $recordType }}</a>@endif
            @if($canEdit)<form method="post" action="{{ route($routeBase.'.status',$record) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $record->is_active?0:1 }}"><button class="btn btn-sm {{ $record->is_active?'btn-outline-danger':'btn-outline-success' }}">{{ $record->is_active?'Deactivate':'Activate' }}</button></form>@endif
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

    <div class="ws-card">
        @include("master.partials.workspace-{$workspaceType}")
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
 const activateTop=(btn)=>{
   const root=btn.closest('.ws-card');
   root.querySelectorAll(':scope > .ws-tabs .ws-tab').forEach(x=>x.classList.remove('active'));
   root.querySelectorAll(':scope > .ws-panel').forEach(x=>x.classList.remove('active'));
   btn.classList.add('active');
   root.querySelector(`:scope > [data-ws-panel="${btn.dataset.wsTab}"]`)?.classList.add('active');
   history.replaceState(null,'','#'+btn.dataset.wsTab);
 };
 document.querySelectorAll('[data-ws-tab]').forEach(btn=>btn.addEventListener('click',()=>activateTop(btn)));
 document.querySelectorAll('[data-subtab]').forEach(btn=>btn.addEventListener('click',()=>{
   const root=btn.closest('.ws-composite');
   root.querySelectorAll('[data-subtab]').forEach(x=>x.classList.remove('active'));
   root.querySelectorAll('[data-subpanel]').forEach(x=>x.classList.remove('active'));
   btn.classList.add('active');
   root.querySelector(`[data-subpanel="${btn.dataset.subtab}"]`)?.classList.add('active');
 }));
 const hash=location.hash.replace('#','');
 if(hash){const btn=document.querySelector(`[data-ws-tab="${hash}"]`); if(btn) activateTop(btn);}
});
</script>
@endsection
