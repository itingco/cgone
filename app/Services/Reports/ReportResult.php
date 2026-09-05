<?php

namespace App\Services\Reports;

final class ReportResult
{
    /** @var array<int,object|array<string,mixed>> */
    public array $rows;

    /**
     * @param array<int,ReportColumn> $columns
     * @param iterable<object|array<string,mixed>> $rows
     * @param array<string,mixed> $summary
     * @param array<string,mixed> $totals
     * @param array<int,mixed> $groups
     * @param array<int,string> $notes
     * @param array<string,mixed> $metadata
     */
    public function __construct(
        public string $title,
        public array $columns,
        iterable $rows,
        public array $summary = [],
        public array $totals = [],
        public array $groups = [],
        public array $notes = [],
        public array $metadata = [],
    ) {
        $this->rows = is_array($rows) ? array_values($rows) : array_values(iterator_to_array($rows));
    }

    public function rowCount(): int
    {
        return count($this->rows);
    }

    public function value(object|array $row, string $key): mixed
    {
        return data_get($row, $key);
    }
}
