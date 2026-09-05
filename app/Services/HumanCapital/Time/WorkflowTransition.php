<?php
namespace App\Services\HumanCapital\Time;

use DomainException;

final class WorkflowTransition
{
    public function assert(string $current, array $allowedFrom, string $target): void
    {
        if (! in_array($current, $allowedFrom, true)) {
            throw new DomainException("Invalid workflow transition {$current} -> {$target}.");
        }
    }
}
