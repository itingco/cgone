<?php
namespace App\Services\HumanCapital\Time;

use App\Models\HumanCapital\Time\{AttendanceCorrection, AttendanceRecord};
use Illuminate\Support\Facades\DB;

final class AttendanceCorrectionService
{
    public function __construct(private readonly WorkflowTransition $workflow, private readonly ShiftTimeCalculator $calculator) {}

    public function submit(AttendanceCorrection $correction, ?int $userId): AttendanceCorrection
    {
        $this->workflow->assert($correction->status, ['DRAFT'], 'SUBMITTED');
        $correction->update(['status'=>'SUBMITTED','requested_by'=>$correction->requested_by ?: $userId,'submitted_at'=>now()]);
        return $correction->fresh();
    }

    public function approve(AttendanceCorrection $correction, int $userId): AttendanceCorrection
    {
        $this->workflow->assert($correction->status, ['SUBMITTED'], 'APPROVED');
        $correction->update(['status'=>'APPROVED','approved_by'=>$userId,'approved_at'=>now(),'rejected_by'=>null,'rejected_at'=>null,'rejection_reason'=>null]);
        return $correction->fresh();
    }

    public function reject(AttendanceCorrection $correction, int $userId, ?string $reason = null): AttendanceCorrection
    {
        $this->workflow->assert($correction->status, ['SUBMITTED'], 'REJECTED');
        $correction->update(['status'=>'REJECTED','rejected_by'=>$userId,'rejected_at'=>now(),'rejection_reason'=>$reason]);
        return $correction->fresh();
    }

    public function effectiveValues(AttendanceRecord $attendance): array
    {
        $approved = $attendance->corrections()->where('status','APPROVED')->orderByDesc('approved_at')->orderByDesc('id')->first();
        $checkIn = $approved?->requested_check_in ?? $attendance->raw_check_in;
        $checkOut = $approved?->requested_check_out ?? $attendance->raw_check_out;
        $scheduledIn = $attendance->scheduled_in;
        $scheduledOut = $attendance->scheduled_out;
        $crossDay = $scheduledIn && $scheduledOut ? $scheduledOut->toDateString() !== $scheduledIn->toDateString() : false;
        $metrics = $this->calculator->calculate(
            $attendance->work_date->toDateString(),
            $scheduledIn?->format('H:i:s'),
            $scheduledOut?->format('H:i:s'),
            $crossDay,
            (int)$attendance->scheduled_break_minutes,
            (int)$attendance->scheduled_grace_late_minutes,
            (bool)$attendance->scheduled_overtime_eligible,
            $checkIn?->format('Y-m-d H:i:s'),
            $checkOut?->format('Y-m-d H:i:s'),
        );
        return [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'correction_id' => $approved?->id,
            'late_minutes' => $metrics['late_minutes'],
            'early_leave_minutes' => $metrics['early_leave_minutes'],
            'working_minutes' => $metrics['working_minutes'],
            'overtime_candidate_minutes' => $metrics['overtime_candidate_minutes'],
        ];
    }
}
