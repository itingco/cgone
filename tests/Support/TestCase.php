<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;
use Throwable;

abstract class TestCase
{
    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException($message ?: sprintf(
                "Expected %s, got %s",
                var_export($expected, true),
                var_export($actual, true)
            ));
        }
    }

    protected function assertTrue(bool $condition, string $message = 'Expected true'): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Expected false'): void
    {
        if ($condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertThrows(string $exceptionClass, callable $callback): void
    {
        try {
            $callback();
        } catch (Throwable $throwable) {
            if ($throwable instanceof $exceptionClass) {
                return;
            }

            throw new RuntimeException(sprintf(
                'Expected %s, got %s: %s',
                $exceptionClass,
                $throwable::class,
                $throwable->getMessage()
            ));
        }

        throw new RuntimeException(sprintf('Expected exception %s was not thrown', $exceptionClass));
    }
}
