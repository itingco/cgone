<?php
namespace App\Services\HumanCapital\Time;

use App\Models\HumanCapital\Time\{EmployeeShiftAssignment, WorkScheduleOverride};
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class ScheduleResolver
{
    public function forEmployeeDate(int $employeeId, CarbonInterface|string $date): ?ResolvedShift
    {
        $day = $date instanceof CarbonInterface ? CarbonImmutable::instance($date) : CarbonImmutable::parse((string) $date);
        $workDate = $day->toDateString();

        $override = WorkScheduleOverride::query()->with('shift')
            ->where('employee_id', $employeeId)->whereDate('work_date', $workDate)->first();
        if ($override) {
            return new ResolvedShift($workDate, $override->is_off ? null : $override->shift, (bool)$override->is_off, 'OVERRIDE');
        }

        $assignment = EmployeeShiftAssignment::query()->with(['pattern.days.shift'])
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $workDate)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $workDate))
            ->orderByDesc('effective_from')->first();
        if (! $assignment) return null;

        $patternDay = $assignment->pattern?->days?->firstWhere('weekday', $day->isoWeekday());
        if (! $patternDay) return null;
        if ($patternDay->is_off) return new ResolvedShift($workDate, null, true, 'PATTERN');
        return new ResolvedShift($workDate, $patternDay->shift, false, 'PATTERN');
    }
}
