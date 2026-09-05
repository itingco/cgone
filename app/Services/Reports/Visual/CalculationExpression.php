<?php

namespace App\Services\Reports\Visual;

final class CalculationExpression
{
    public const OPERATIONS = ['add','subtract','multiply','divide'];

    public function __construct(public readonly array $definition)
    {
    }
}
