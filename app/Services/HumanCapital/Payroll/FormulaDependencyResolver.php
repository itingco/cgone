<?php

namespace App\Services\HumanCapital\Payroll;

use DomainException;

final class FormulaDependencyResolver
{
    public function __construct(private readonly FormulaEngine $engine)
    {
    }

    public function order(iterable $components): array
    {
        $formulas = [];
        foreach ($components as $component) {
            $row = is_array($component) ? $component : [
                'code' => $component->code,
                'formula_expression' => $component->formula_expression ?? $component->formula?->expression ?? null,
            ];
            $code = strtoupper(trim((string) ($row['code'] ?? '')));
            if ($code === '') throw new DomainException('Salary component code is required.');
            if (isset($formulas[$code])) throw new DomainException("Duplicate salary component code {$code}.");
            $formulas[$code] = trim((string) ($row['formula_expression'] ?? '')) ?: null;
        }

        $deps = [];
        foreach ($formulas as $code => $formula) {
            $deps[$code] = [];
            if ($formula === null) continue;
            foreach ($this->engine->identifiers($formula) as $identifier) {
                if (array_key_exists($identifier, $formulas)) $deps[$code][$identifier] = true;
            }
        }

        $visiting = [];
        $visited = [];
        $ordered = [];

        $visit = function (string $code) use (&$visit, &$visiting, &$visited, &$ordered, $deps): void {
            if (isset($visited[$code])) return;
            if (isset($visiting[$code])) throw new DomainException("Circular salary component dependency detected at {$code}.");
            $visiting[$code] = true;
            foreach (array_keys($deps[$code] ?? []) as $dependency) $visit($dependency);
            unset($visiting[$code]);
            $visited[$code] = true;
            $ordered[] = $code;
        };

        foreach (array_keys($formulas) as $code) $visit($code);
        return $ordered;
    }
}
