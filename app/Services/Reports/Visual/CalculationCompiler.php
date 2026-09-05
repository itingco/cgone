<?php

namespace App\Services\Reports\Visual;

use DomainException;

final class CalculationCompiler
{
    public function evaluate(array $expression, array|object $row, array $allowedFields): float
    {
        $op = strtolower((string)($expression['op'] ?? ''));
        if (! in_array($op, CalculationExpression::OPERATIONS, true)) {
            throw new DomainException("Unsupported calculation operation [{$op}].");
        }

        $left = $this->operand((array)($expression['left'] ?? []), $row, $allowedFields);
        $right = $this->operand((array)($expression['right'] ?? []), $row, $allowedFields);

        $value = match ($op) {
            'add' => $left + $right,
            'subtract' => $left - $right,
            'multiply' => $left * $right,
            'divide' => abs($right) < 0.0000000001 ? 0.0 : $left / $right,
        };

        if (array_key_exists('multiply_by', $expression)) {
            $value *= (float)$expression['multiply_by'];
        }

        return round((float)$value, 8);
    }

    private function operand(array $operand, array|object $row, array $allowedFields): float
    {
        if (array_key_exists('value', $operand)) return (float)$operand['value'];

        $field = (string)($operand['field'] ?? '');
        if (! in_array($field, $allowedFields, true)) {
            throw new DomainException("Calculated field references unavailable field [{$field}].");
        }

        if (is_array($row)) return (float)($row[$field] ?? 0);
        return (float)($row->{$field} ?? 0);
    }
}
