@if(!empty($result->summary))
<div class="row g-3 mb-3">
    @foreach($result->summary as $key => $value)
        <div class="col-md-6 col-xl-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted text-uppercase fw-bold" style="letter-spacing:.05em">{{ ucwords(str_replace('_',' ',$key)) }}</div>
                <div class="fs-4 fw-bold mt-1">
                    @if(is_bool($value))
                        {{ $value ? 'Yes' : 'No' }}
                    @elseif(is_numeric($value))
                        {{ number_format((float)$value,2,',','.') }}
                    @else
                        {{ $value }}
                    @endif
                </div>
            </div></div>
        </div>
    @endforeach
</div>
@endif
