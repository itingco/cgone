@foreach(collect($fields)->groupBy('group_label') as $group=>$rows)
<div class="mb-3">
    <div class="small fw-bold text-uppercase text-muted mb-1">{{ $group ?: 'Fields' }}</div>
    @foreach($rows as $field)
        <div class="d-flex justify-content-between gap-2 py-1 border-bottom">
            <span>{{ $field['label'] }}</span><span class="badge text-bg-light">{{ $field['data_type'] }}</span>
        </div>
    @endforeach
</div>
@endforeach
