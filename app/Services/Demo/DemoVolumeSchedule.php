<?php

namespace App\Services\Demo;

use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use InvalidArgumentException;

final class DemoVolumeSchedule
{
    public function __construct(
        private readonly int $months = 6,
        private readonly ?int $days = null,
        private readonly int $salesPerDay = 12,
        private readonly int $purchasePerDay = 6,
        private readonly int $inventoryPerDay = 4,
    ) {
        if ($months < 1 || $months > 24) {
            throw new InvalidArgumentException('months must be between 1 and 24.');
        }
        if ($days !== null && ($days < 1 || $days > 30)) {
            throw new InvalidArgumentException('days must be between 1 and 30.');
        }
    }

    public function mode(): string
    {
        return $this->days !== null ? 'days' : 'months';
    }

    public function startDate(DateTimeImmutable $baseDate): DateTimeImmutable
    {
        if ($this->days !== null) {
            return $baseDate->setTime(0, 0);
        }

        return $baseDate
            ->modify('first day of this month')
            ->modify('-'.($this->months - 1).' months')
            ->setTime(0, 0);
    }

    public function endDate(DateTimeImmutable $baseDate): DateTimeImmutable
    {
        if ($this->days !== null) {
            return $baseDate->modify('+'.($this->days - 1).' days')->setTime(0, 0);
        }

        return $baseDate->setTime(0, 0);
    }

    /** @return list<DateTimeImmutable> */
    public function dates(DateTimeImmutable $baseDate): array
    {
        $start = $this->startDate($baseDate);
        $end = $this->endDate($baseDate);
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));

        return iterator_to_array($period, false);
    }

    /** @return array{sales:int,purchase:int,inventory:int} */
    public function countsForDate(DateTimeImmutable $date): array
    {
        return [
            'sales' => $this->weightedCount($this->salesPerDay, $date, 'sales'),
            'purchase' => $this->weightedCount($this->purchasePerDay, $date, 'purchase'),
            'inventory' => $this->weightedCount($this->inventoryPerDay, $date, 'inventory'),
        ];
    }

    private function weightedCount(int $base, DateTimeImmutable $date, string $kind): int
    {
        if ($base <= 0) {
            return 0;
        }

        $weekday = (int) $date->format('N');
        $weekFactor = $weekday <= 5 ? 1.0 : ($weekday === 6 ? 0.60 : 0.25);

        $seed = (int) sprintf('%u', crc32($kind.'-'.$date->format('Y-m-d')));
        $variance = (($seed % 41) - 20) / 100; // deterministic -20% .. +20%
        $count = (int) round($base * $weekFactor * (1 + $variance));

        return max(0, $count);
    }
}
