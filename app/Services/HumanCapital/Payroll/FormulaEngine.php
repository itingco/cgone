<?php

namespace App\Services\HumanCapital\Payroll;

use DomainException;

final class FormulaEngine
{
    private array $tokens = [];
    private int $position = 0;
    private array $context = [];
    private bool $validationOnly = false;

    public function evaluate(string $expression, array $context): float
    {
        $this->validationOnly = false;
        $this->tokens = $this->tokenize($expression);
        $this->position = 0;
        $this->context = [];
        foreach ($context as $key => $value) {
            if (! is_numeric($value)) {
                throw new DomainException("Formula context {$key} must be numeric.");
            }
            $this->context[strtoupper((string) $key)] = (float) $value;
        }

        if ($this->tokens === []) {
            throw new DomainException('Formula expression cannot be empty.');
        }

        $value = $this->parseExpression();
        if ($this->current() !== null) {
            throw new DomainException('Unexpected token '.$this->current()['value'].'.');
        }

        if (! is_finite($value)) {
            throw new DomainException('Formula result is not finite.');
        }

        return $value;
    }


    public function validate(string $expression, array $allowedIdentifiers = []): void
    {
        $ids = $this->identifiers($expression);
        $allowed = array_fill_keys(array_map('strtoupper', $allowedIdentifiers), true);
        if ($allowedIdentifiers !== []) {
            foreach ($ids as $id) {
                if (! isset($allowed[$id])) throw new DomainException("Unknown formula identifier {$id}.");
            }
        }

        $this->validationOnly = true;
        $this->tokens = $this->tokenize($expression);
        $this->position = 0;
        $this->context = array_fill_keys($ids, 1.0);
        if ($this->tokens === []) throw new DomainException('Formula expression cannot be empty.');
        $this->parseExpression();
        if ($this->current() !== null) throw new DomainException('Unexpected token '.$this->current()['value'].'.');
        $this->validationOnly = false;
    }

    public function identifiers(string $expression): array
    {
        $tokens = $this->tokenize($expression);
        $functions = ['MIN','MAX','ROUND'];
        $ids = [];
        foreach ($tokens as $token) {
            if ($token['type'] !== 'identifier') continue;
            $name = strtoupper($token['value']);
            if (in_array($name, $functions, true)) continue;
            $ids[$name] = true;
        }
        return array_keys($ids);
    }

    private function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;

        while ($i < $length) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;
                continue;
            }

            if (str_contains('+-*/(),', $char)) {
                $tokens[] = ['type' => $char, 'value' => $char];
                $i++;
                continue;
            }

            if (ctype_digit($char) || $char === '.') {
                $start = $i;
                $dots = 0;
                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    if ($expression[$i] === '.') $dots++;
                    $i++;
                }
                $raw = substr($expression, $start, $i - $start);
                if ($dots > 1 || $raw === '.' || ! is_numeric($raw)) {
                    throw new DomainException("Invalid number {$raw}.");
                }
                $tokens[] = ['type' => 'number', 'value' => $raw];
                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) $i++;
                $tokens[] = ['type' => 'identifier', 'value' => substr($expression, $start, $i - $start)];
                continue;
            }

            throw new DomainException("Unsafe or unsupported formula token '{$char}'.");
        }

        return $tokens;
    }

    private function parseExpression(): float
    {
        $value = $this->parseTerm();
        while (($token = $this->current()) && in_array($token['type'], ['+','-'], true)) {
            $this->position++;
            $rhs = $this->parseTerm();
            $value = $token['type'] === '+' ? $value + $rhs : $value - $rhs;
        }
        return $value;
    }

    private function parseTerm(): float
    {
        $value = $this->parseUnary();
        while (($token = $this->current()) && in_array($token['type'], ['*','/'], true)) {
            $this->position++;
            $rhs = $this->parseUnary();
            if ($token['type'] === '/') {
                if (abs($rhs) < 0.000000000001) {
                    if ($this->validationOnly) {
                        $value = 1.0;
                        continue;
                    }
                    throw new DomainException('Division by zero is not allowed.');
                }
                $value /= $rhs;
            } else {
                $value *= $rhs;
            }
        }
        return $value;
    }

    private function parseUnary(): float
    {
        $token = $this->current();
        if ($token && in_array($token['type'], ['+','-'], true)) {
            $this->position++;
            $value = $this->parseUnary();
            return $token['type'] === '-' ? -$value : $value;
        }
        return $this->parsePrimary();
    }

    private function parsePrimary(): float
    {
        $token = $this->current();
        if ($token === null) throw new DomainException('Unexpected end of formula.');

        if ($token['type'] === 'number') {
            $this->position++;
            return (float) $token['value'];
        }

        if ($token['type'] === '(') {
            $this->position++;
            $value = $this->parseExpression();
            $this->expect(')');
            return $value;
        }

        if ($token['type'] === 'identifier') {
            $name = strtoupper($token['value']);
            $this->position++;

            $next = $this->current();
            if (($next['type'] ?? null) === '(') {
                return $this->parseFunction($name);
            }

            if (! array_key_exists($name, $this->context)) {
                throw new DomainException("Unknown formula identifier {$name}.");
            }
            return $this->context[$name];
        }

        throw new DomainException('Unexpected token '.$token['value'].'.');
    }

    private function parseFunction(string $name): float
    {
        if (! in_array($name, ['MIN','MAX','ROUND'], true)) {
            throw new DomainException("Unsupported formula function {$name}.");
        }

        $this->expect('(');
        $args = [];
        $next = $this->current();
        if (($next['type'] ?? null) !== ')') {
            while (true) {
                $args[] = $this->parseExpression();
                $next = $this->current();
                if (($next['type'] ?? null) !== ',') break;
                $this->position++;
            }
        }
        $this->expect(')');

        return match ($name) {
            'MIN' => $args ? min($args) : throw new DomainException('MIN requires at least one argument.'),
            'MAX' => $args ? max($args) : throw new DomainException('MAX requires at least one argument.'),
            'ROUND' => $this->roundFunction($args),
        };
    }

    private function roundFunction(array $args): float
    {
        if (count($args) < 1 || count($args) > 2) {
            throw new DomainException('ROUND requires one or two arguments.');
        }
        $precision = isset($args[1]) ? (int) $args[1] : 0;
        if ($precision < -6 || $precision > 6) {
            throw new DomainException('ROUND precision must be between -6 and 6.');
        }
        return round($args[0], $precision);
    }

    private function expect(string $type): void
    {
        $token = $this->current();
        if ($token === null || $token['type'] !== $type) {
            throw new DomainException("Expected {$type}.");
        }
        $this->position++;
    }

    private function current(): ?array
    {
        return $this->tokens[$this->position] ?? null;
    }
}
