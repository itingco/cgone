<?php

namespace Tests\Unit\Reports;

use App\Services\Reports\Visual\CalculationCompiler;
use DomainException;
use Tests\TestCase;

class CalculationCompilerTest extends TestCase
{
    public function test_calculator_supports_safe_arithmetic_and_multiplier(): void
    {
        $compiler = app(CalculationCompiler::class);
        $row = ['sales' => 100.0, 'cost' => 70.0];

        $gp = $compiler->evaluate([
            'op' => 'subtract',
            'left' => ['field' => 'sales'],
            'right' => ['field' => 'cost'],
        ], $row, ['sales','cost']);

        $margin = $compiler->evaluate([
            'op' => 'divide',
            'left' => ['value' => $gp],
            'right' => ['field' => 'sales'],
            'multiply_by' => 100,
        ], $row, ['sales','cost']);

        $this->assertSame(30.0, $gp);
        $this->assertSame(30.0, $margin);
    }

    public function test_division_by_zero_returns_zero(): void
    {
        $value = app(CalculationCompiler::class)->evaluate([
            'op' => 'divide',
            'left' => ['field' => 'sales'],
            'right' => ['field' => 'zero'],
        ], ['sales' => 10, 'zero' => 0], ['sales','zero']);

        $this->assertSame(0.0, $value);
    }

    public function test_unknown_field_is_rejected(): void
    {
        $this->expectException(DomainException::class);
        app(CalculationCompiler::class)->evaluate(
            ['op' => 'add', 'left' => ['field' => 'secret'], 'right' => ['value' => 1]],
            [],
            ['sales']
        );
    }
}
