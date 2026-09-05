@php
    $hasSensitiveSqlParameters = $definition->report_type === \App\Models\Reports\ReportDefinition::TYPE_SQL
        && collect($schema)->contains(fn($config)=>(bool)($config['sensitive'] ?? false));
@endphp
<form method="{{ $hasSensitiveSqlParameters ? 'post' : 'get' }}" action="{{ route('reports.run',$definition) }}" class="row g-3 align-items-end">
    @if($hasSensitiveSqlParameters)
        @csrf
    @endif
    @if($selectedView)<input type="hidden" name="view_id" value="{{ $selectedView->id }}">@endif
    @foreach($schema as $key => $config)
        @php
            $isSensitive=(bool)($config['sensitive'] ?? false);
            $fieldValue=$isSensitive ? '' : ($parameters[$key] ?? '');
        @endphp
        <div class="col-md-4 col-xl-3">
            <label class="form-label small fw-semibold">{{ $config['label'] ?? ucwords(str_replace('_',' ',$key)) }}</label>
            @if(($config['type'] ?? 'string') === 'date')
                <input class="form-control" type="date" name="{{ $key }}" value="{{ $fieldValue }}">
            @elseif(($config['type'] ?? 'string') === 'datetime')
                <input class="form-control" type="datetime-local" name="{{ $key }}" value="{{ $fieldValue ? str_replace(' ','T',substr((string)$fieldValue,0,16)) : '' }}">
            @elseif(($config['type'] ?? 'string') === 'boolean')
                <select class="form-select" name="{{ $key }}">
                    @if(($config['nullable'] ?? false))<option value="">All</option>@endif
                    <option value="1" @selected((string)$fieldValue==='1' || $fieldValue===true)>Yes</option>
                    <option value="0" @selected((string)$fieldValue==='0' || $fieldValue===false)>No</option>
                </select>
            @elseif(in_array(($config['type'] ?? 'string'),['business_unit','lookup','choice'],true))
                <select class="form-select" name="{{ $key }}">
                    @if(($config['nullable'] ?? false) || in_array(($config['type'] ?? ''),['business_unit','lookup'],true))
                        <option value="">{{ ($config['type'] ?? '') === 'business_unit' ? 'All Business Units' : 'All' }}</option>
                    @endif
                    @foreach(($filterOptions[$key] ?? collect()) as $option)
                        <option value="{{ $option->id }}" @selected((string)$fieldValue === (string)$option->id)>{{ $option->code }}{{ ($option->name ?? null) && (string)$option->name !== (string)$option->code ? ' - '.$option->name : '' }}</option>
                    @endforeach
                </select>
            @elseif(($config['type'] ?? 'string') === 'integer')
                <input class="form-control" type="number" step="1" name="{{ $key }}" value="{{ $fieldValue }}">
            @elseif(($config['type'] ?? 'string') === 'decimal')
                <input class="form-control" type="number" step="0.0001" name="{{ $key }}" value="{{ $fieldValue }}">
            @else
                <input class="form-control" type="{{ $isSensitive ? 'password' : 'text' }}" name="{{ $key }}" value="{{ $fieldValue }}" @if($isSensitive) autocomplete="off" @endif>
            @endif
            @if($isSensitive)
                <div class="form-text">Sensitive value is sent by POST and is never placed in the report URL or Saved View.</div>
            @endif
        </div>
    @endforeach
    <div class="col-auto"><button class="btn btn-primary">Refresh</button></div>
    <div class="col-auto"><a class="btn btn-light" href="{{ route('reports.run',$definition) }}">Reset</a></div>
</form>
