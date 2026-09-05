<?php

namespace App\Services\Reports\Datasources;

use InvalidArgumentException;
use LogicException;

final class ReportDatasourceRegistry
{
    /** @return array<string,class-string<ReportDatasourceAdapter>> */
    public function definitions(): array
    {
        return (array) config('reports.datasources', []);
    }

    public function resolve(string $code): ReportDatasourceAdapter
    {
        $code = strtoupper($code);
        $class = $this->definitions()[$code] ?? null;

        if (! $class) {
            throw new InvalidArgumentException("Report datasource [{$code}] is not registered.");
        }

        $source = app($class);
        if (! $source instanceof ReportDatasourceAdapter) {
            throw new LogicException("Datasource [{$code}] must implement ReportDatasourceAdapter.");
        }
        if (strtoupper($source->code()) !== $code) {
            throw new LogicException("Datasource class [{$class}] returned mismatched code [{$source->code()}].");
        }

        return $source;
    }

    /** @return array<string,ReportDatasourceAdapter> */
    public function all(): array
    {
        $rows = [];
        foreach (array_keys($this->definitions()) as $code) {
            $rows[$code] = $this->resolve($code);
        }
        return $rows;
    }
}
