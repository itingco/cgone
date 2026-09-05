<?php

namespace App\Services\Reports;

final class ReportRowLimitPolicy
{
    public function visual(bool $preview = false, ?string $exportType = null): int
    {
        if ($preview) {
            return $this->configured('reports.preview_row_limit', 500);
        }

        if ($exportType !== null && $exportType !== '') {
            return $this->configured('reports.export_row_limit', $this->configured('reports.sql_export_row_limit', 25000));
        }

        return $this->configured('reports.screen_row_limit', 5000);
    }

    public function standard(?string $exportType = null): int
    {
        return $exportType !== null && $exportType !== ''
            ? $this->configured('reports.export_row_limit', $this->configured('reports.sql_export_row_limit', 25000))
            : $this->configured('reports.screen_row_limit', 5000);
    }

    private function configured(string $key, int $default): int
    {
        if (! function_exists('config')) {
            return $default;
        }

        return max(1, (int) config($key, $default));
    }
}
