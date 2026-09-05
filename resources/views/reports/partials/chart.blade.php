@php $chart=$result->metadata['chart']??null; @endphp
@if($chart && count($chart['labels']??[])>0)
<div class="card mb-3"><div class="card-header bg-white"><strong>Chart</strong></div><div class="card-body"><div style="height:320px"><canvas id="report-chart"></canvas></div></div></div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(()=>{const c=@json($chart);const type=c.type==='column'?'bar':(c.type==='donut'?'doughnut':c.type);new Chart(document.getElementById('report-chart'),{type,data:{labels:c.labels,datasets:[{label:c.value_field,data:c.data}]},options:{responsive:true,maintainAspectRatio:false}})})();
</script>
@endpush
@endif
