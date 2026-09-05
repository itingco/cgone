<?php

namespace App\Services\Reports\Export;

use App\Services\Reports\ReportResult;

final class ReportCsvExporter
{
    public function content(ReportResult $result): string
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");

        fputcsv($stream, array_map(fn ($column) => $this->safeCell($column->label), $result->columns));

        foreach ($result->rows as $row) {
            fputcsv(
                $stream,
                array_map(fn ($column) => $this->safeCell($result->value($row, $column->key)), $result->columns)
            );
        }

        if ($result->totals !== []) {
            $line = [];
            foreach ($result->columns as $i => $column) {
                $line[] = $i === 0
                    ? 'GRAND TOTAL'
                    : $this->safeCell($result->totals[$column->key] ?? '');
            }
            fputcsv($stream, $line);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    private function safeCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        // Prevent spreadsheet applications from interpreting exported text as formulas.
        return preg_match('/^[=+\-@\t\r]/u', $value) ? "'".$value : $value;
    }
}

