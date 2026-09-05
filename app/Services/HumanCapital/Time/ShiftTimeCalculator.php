<?php
namespace App\Services\HumanCapital\Time;

use DateTimeImmutable;

final class ShiftTimeCalculator
{
    public function calculate(
        string $workDate,
        ?string $shiftStart,
        ?string $shiftEnd,
        bool $crossDay,
        int $breakMinutes,
        int $graceLateMinutes,
        bool $overtimeEligible,
        ?string $rawCheckIn,
        ?string $rawCheckOut,
    ): array {
        $scheduledIn = $shiftStart ? new DateTimeImmutable($workDate.' '.$shiftStart) : null;
        $scheduledOut = $shiftEnd ? new DateTimeImmutable($workDate.' '.$shiftEnd) : null;
        if ($scheduledIn && $scheduledOut && ($crossDay || $scheduledOut <= $scheduledIn)) {
            $scheduledOut = $scheduledOut->modify('+1 day');
        }

        $checkIn = $rawCheckIn ? new DateTimeImmutable($rawCheckIn) : null;
        $checkOut = $rawCheckOut ? new DateTimeImmutable($rawCheckOut) : null;
        if ($checkIn && $checkOut && $checkOut < $checkIn) $checkOut = $checkOut->modify('+1 day');

        $late = 0;
        if ($scheduledIn && $checkIn) {
            $threshold = $scheduledIn->modify('+'.max(0, $graceLateMinutes).' minutes');
            if ($checkIn > $threshold) $late = $this->minutesBetween($threshold, $checkIn);
        }

        $early = 0;
        if ($scheduledOut && $checkOut && $checkOut < $scheduledOut) $early = $this->minutesBetween($checkOut, $scheduledOut);

        $working = 0;
        if ($checkIn && $checkOut && $checkOut > $checkIn) {
            $working = max(0, $this->minutesBetween($checkIn, $checkOut) - max(0, $breakMinutes));
        }

        $overtime = 0;
        if ($overtimeEligible && $scheduledOut && $checkOut && $checkOut > $scheduledOut) {
            $overtime = $this->minutesBetween($scheduledOut, $checkOut);
        }

        return [
            'scheduled_in' => $scheduledIn?->format('Y-m-d H:i:s'),
            'scheduled_out' => $scheduledOut?->format('Y-m-d H:i:s'),
            'late_minutes' => $late,
            'early_leave_minutes' => $early,
            'working_minutes' => $working,
            'overtime_candidate_minutes' => $overtime,
            'cross_day' => $crossDay,
        ];
    }

    private function minutesBetween(DateTimeImmutable $from, DateTimeImmutable $to): int
    {
        return max(0, (int) floor(($to->getTimestamp() - $from->getTimestamp()) / 60));
    }
}
