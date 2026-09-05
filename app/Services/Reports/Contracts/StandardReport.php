<?php

namespace App\Services\Reports\Contracts;

use App\Services\Reports\ReportResult;

interface StandardReport
{
    public function code(): string;

    /**
     * @return array<string,array<string,mixed>>
     */
    public function parameters(): array;

    public function run(array $parameters): ReportResult;
}
