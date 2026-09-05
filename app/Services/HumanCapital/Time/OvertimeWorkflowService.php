<?php
namespace App\Services\HumanCapital\Time;

use App\Models\HumanCapital\Time\OvertimeRecord;
use DomainException;

final class OvertimeWorkflowService
{
    public function __construct(private readonly WorkflowTransition $workflow) {}

    public function submit(OvertimeRecord $record, ?int $userId): OvertimeRecord
    {
        $this->workflow->assert($record->status,['DRAFT'],'SUBMITTED');
        $record->update(['status'=>'SUBMITTED','requested_by'=>$record->requested_by ?: $userId,'submitted_at'=>now()]);
        return $record->fresh();
    }

    public function approve(OvertimeRecord $record, int $userId, float $approvedHours, ?float $ratePercent = null): OvertimeRecord
    {
        $this->workflow->assert($record->status,['SUBMITTED'],'APPROVED');
        if ($approvedHours < 0 || $approvedHours > (float)$record->actual_hours) throw new DomainException('Approved overtime hours must be between 0 and actual hours.');
        $rate = $ratePercent ?? (float)($record->rate_percent ?: $record->overtimeType?->default_rate_percent ?: 100);
        if (! in_array((float)$rate,[100.0,150.0,200.0,300.0,400.0],true) && $rate <= 0) throw new DomainException('Overtime rate percent must be greater than zero.');
        $record->update(['status'=>'APPROVED','approved_hours'=>$approvedHours,'rate_percent'=>$rate,'approved_by'=>$userId,'approved_at'=>now()]);
        return $record->fresh();
    }

    public function reject(OvertimeRecord $record, int $userId, ?string $reason = null): OvertimeRecord
    {
        $this->workflow->assert($record->status,['SUBMITTED'],'REJECTED');
        $record->update(['status'=>'REJECTED','approved_hours'=>0,'rejected_by'=>$userId,'rejected_at'=>now(),'rejection_reason'=>$reason]);
        return $record->fresh();
    }
}
