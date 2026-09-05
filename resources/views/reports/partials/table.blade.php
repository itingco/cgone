@php
    $formatValue = function($value,$column){
        if($value===null || $value==='') return '-';
        try{
            if($column->type==='date') return \Carbon\Carbon::parse($value)->format('d/m/Y');
            if($column->type==='datetime') return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
        }catch(\Throwable $e){}
        if(in_array($column->type,['money','number','quantity','percent'],true) && is_numeric($value)){
            return number_format((float)$value,$column->decimals,',','.');
        }
        return $value;
    };
    $drillRegistry = isset($definition) ? app(\App\Services\Reports\Drilldown\DrilldownRegistry::class) : null;
@endphp
<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr>
                @foreach($result->columns as $column)
                    <th class="{{ in_array($column->type,['money','number','quantity','percent'],true)?'text-end':'' }}">{{ $column->label }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @forelse($result->rows as $row)
                    <tr>
                        @foreach($result->columns as $column)
                            @php
                                $value=$result->value($row,$column->key);
                                $drillUrl=$drillRegistry?->signedUrl($definition,$column->key,$row,$parameters??[]);
                            @endphp
                            <td class="{{ in_array($column->type,['money','number','quantity','percent'],true)?'text-end':'' }}">
                                @if($drillUrl && $value!==null && $value!=='')
                                    <a href="{{ $drillUrl }}" class="text-decoration-none fw-semibold" title="Drill down">{{ $formatValue($value,$column) }}</a>
                                @else
                                    {{ $formatValue($value,$column) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($result->columns) }}" class="text-center text-muted py-5">No data for the selected filters.</td></tr>
                @endforelse
            </tbody>
            @if(!empty($result->totals))
                <tfoot class="table-light"><tr>
                    @foreach($result->columns as $index => $column)
                        <th class="{{ in_array($column->type,['money','number','quantity','percent'],true)?'text-end':'' }}">
                            @if($index===0)
                                GRAND TOTAL
                            @elseif(array_key_exists($column->key,$result->totals))
                                {{ $formatValue($result->totals[$column->key],$column) }}
                            @endif
                        </th>
                    @endforeach
                </tr></tfoot>
            @endif
        </table>
    </div>
</div>
