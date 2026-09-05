<?php

namespace App\Services\Reports\Comparison;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final class PeriodComparisonService
{
    public const NONE = 'NONE';
    public const PREVIOUS_PERIOD = 'PREVIOUS_PERIOD';
    public const SAME_PERIOD_LAST_YEAR = 'SAME_PERIOD_LAST_YEAR';

    /**
     * @return array{from:?CarbonImmutable,to:?CarbonImmutable,label:string,mode:string}
     */
    public function range(CarbonImmutable|string $from, CarbonImmutable|string $to, string $mode): array
    {
        $from = $from instanceof CarbonImmutable ? $from->startOfDay() : CarbonImmutable::parse($from)->startOfDay();
        $to = $to instanceof CarbonImmutable ? $to->startOfDay() : CarbonImmutable::parse($to)->startOfDay();

        if ($to->lessThan($from)) {
            throw new InvalidArgumentException('Comparison period end date cannot be before start date.');
        }

        return match ($mode) {
            self::NONE => ['from'=>null,'to'=>null,'label'=>'Comparison','mode'=>self::NONE],
            self::PREVIOUS_PERIOD => $this->previousPeriod($from,$to),
            self::SAME_PERIOD_LAST_YEAR => [
                'from'=>$from->subYearNoOverflow(),
                'to'=>$to->subYearNoOverflow(),
                'label'=>'Same Period Last Year',
                'mode'=>self::SAME_PERIOD_LAST_YEAR,
            ],
            default => throw new InvalidArgumentException('Unknown comparison mode.'),
        };
    }

    /** @return array{from:CarbonImmutable,to:CarbonImmutable,label:string,mode:string} */
    private function previousPeriod(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $days = $from->diffInDays($to) + 1;
        $compareTo = $from->subDay();
        $compareFrom = $compareTo->subDays($days - 1);

        return [
            'from'=>$compareFrom,
            'to'=>$compareTo,
            'label'=>'Previous Period',
            'mode'=>self::PREVIOUS_PERIOD,
        ];
    }

    public function variance(float $current, float $comparison): array
    {
        $variance=$current-$comparison;
        return [
            'variance'=>$variance,
            'variance_pct'=>abs($comparison)>0.0001?($variance/$comparison)*100:0.0,
        ];
    }
}
