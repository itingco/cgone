<?php

namespace App\Services\Reports;

use App\Services\Reports\Contracts\StandardReport;
use InvalidArgumentException;
use LogicException;

final class StandardReportRegistry
{
    /** @return array<string,class-string<StandardReport>> */
    public function definitions(): array
    {
        return (array) config('reports.standard', []);
    }

    public function resolve(string $code): StandardReport
    {
        $code = strtoupper($code);
        $class = $this->definitions()[$code] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Standard report [{$code}] is not registered.");
        }

        $report = app($class);

        if (! $report instanceof StandardReport) {
            throw new LogicException("Registered report [{$code}] must implement StandardReport.");
        }

        if (strtoupper($report->code()) !== $code) {
            throw new LogicException("Registered report class [{$class}] returned mismatched code [{$report->code()}].");
        }

        return $report;
    }
}
