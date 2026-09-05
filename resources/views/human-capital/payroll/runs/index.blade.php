@extends('layouts.app')
@section('title','Payroll Calculation')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><div><h4 class="mb-1">Payroll Calculation</h4><div class="small text-muted">Historical snapshot calculation. H4 results remain recalculable until H5 finalization.</div></div><a class="btn btn-primary" href="{{ route('payroll.runs.create') }}">+ New Payroll Period</a></div>
<div class="card"><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Period</th><th>Salary Period</th><th>Payment</th><th>Status</th><th>Latest Run</th><th class="text-end">THP</th><th></th></tr></thead><tbody>
@forelse($periods as $period) @php($run=$period->runs->first()) <tr><td><strong>{{ $period->period_code }}</strong><div class="small text-muted">{{ $period->name }}</div></td><td>{{ $period->salary_period_start?->toDateString() }} – {{ $period->salary_period_end?->toDateString() }}</td><td>{{ $period->payment_date?->toDateString() }}</td><td>{{ $period->status }}</td><td>{{ $run ? '#'.$run->run_no.' '.$run->status : '-' }}</td><td class="text-end">Rp{{ number_format((float)($run?->take_home_pay??0),0,',','.') }}</td><td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('payroll.runs.show',$period) }}">Open</a></td></tr>
@empty <tr><td colspan="7" class="text-center text-muted py-4">No payroll period.</td></tr> @endforelse
</tbody></table></div></div><div class="mt-3">{{ $periods->links() }}</div>
@endsection
