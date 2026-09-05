<?php

namespace App\Services\Reports;

final class ReportColumn
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $type = 'text',
        public readonly int $decimals = 2,
    ) {
    }
}
