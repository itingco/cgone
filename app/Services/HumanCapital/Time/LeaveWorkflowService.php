<?php
namespace App\Services\HumanCapital\Time;

use App\Models\HumanCapital\Time\{LeaveBalance, LeaveRequest};
use DomainException;
use Illuminate\Support\Facades\DB;

final class LeaveWorkflowService
{
    public function __construct(private readonly WorkflowTransition $workflow) {}

    public function assertNoOverlap(LeaveRequest $request): void
    {
        $exists = LeaveRequest::query()->where('employee_id',$request->employee_id)
            ->where('id','!=',$request->id ?: 0)
            ->whereNotIn('status',['REJECTED','CANCELLED'])
            ->whereDate('start_date','<=',$request->end_date)
            ->whereDate('end_date','>=',$request->start_date)->exists();
        if ($exists) throw new DomainException('Leave period overlaps another active leave request.');
    }

    public function submit(LeaveRequest $request, ?int $userId): LeaveRequest
    {
        $this->workflow->assert($request->status,['DRAFT'],'SUBMITTED');
        $this->assertNoOverlap($request);
        $request->update(['status'=>'SUBMITTED','requested_by'=>$request->requested_by ?: $userId,'submitted_at'=>now()]);
        return $request->fresh();
    }

    public function approve(LeaveRequest $request, int $userId): LeaveRequest
    {
        $this->workflow->assert($request->status,['SUBMITTED'],'APPROVED');
        return DB::transaction(function () use ($request,$userId): LeaveRequest {
            $request->refresh();
            $this->workflow->assert($request->status,['SUBMITTED'],'APPROVED');
            $type = $request->leaveType()->firstOrFail();
            if ($type->deduct_balance && ! $request->balance_applied_at) {
                $year = (int)$request->start_date->format('Y');
                if ((int)$request->end_date->format('Y') !== $year) throw new DomainException('Leave balance deduction cannot cross calendar years in one request.');
                $balance = LeaveBalance::query()->where(['employee_id'=>$request->employee_id,'leave_type_id'=>$request->leave_type_id,'year'=>$year])->lockForUpdate()->first();
                if (! $balance) throw new DomainException('Leave balance is not configured for this employee/year.');
                $days = (float)$request->total_days;
                if ($balance->availableDays() < $days) throw new DomainException('Insufficient leave balance.');
                $balance->used_days = (float)$balance->used_days + $days;
                $balance->save();
                $request->balance_applied_at = now();
            }
            $request->status='APPROVED'; $request->approved_by=$userId; $request->approved_at=now(); $request->save();
            return $request->fresh();
        });
    }

    public function reject(LeaveRequest $request, int $userId, ?string $reason = null): LeaveRequest
    {
        $this->workflow->assert($request->status,['SUBMITTED'],'REJECTED');
        $request->update(['status'=>'REJECTED','rejected_by'=>$userId,'rejected_at'=>now(),'rejection_reason'=>$reason]);
        return $request->fresh();
    }

    public function cancel(LeaveRequest $request, int $userId): LeaveRequest
    {
        $this->workflow->assert($request->status,['DRAFT','SUBMITTED','APPROVED'],'CANCELLED');
        return DB::transaction(function () use ($request,$userId): LeaveRequest {
            $request->refresh();
            if ($request->status === 'APPROVED' && $request->balance_applied_at && ! $request->balance_reversed_at) {
                $year = (int)$request->start_date->format('Y');
                $balance = LeaveBalance::query()->where(['employee_id'=>$request->employee_id,'leave_type_id'=>$request->leave_type_id,'year'=>$year])->lockForUpdate()->first();
                if ($balance) { $balance->used_days=max(0,(float)$balance->used_days-(float)$request->total_days); $balance->save(); }
                $request->balance_reversed_at=now();
            }
            $request->status='CANCELLED'; $request->cancelled_by=$userId; $request->cancelled_at=now(); $request->save();
            return $request->fresh();
        });
    }
}
