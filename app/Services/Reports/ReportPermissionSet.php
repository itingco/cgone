<?php

namespace App\Services\Reports;

final class ReportPermissionSet
{
    /** @param array<string,bool> $grants */
    public function __construct(private array $grants)
    {
    }

    public function allows(string $permission): bool
    {
        return (bool) ($this->grants[$permission] ?? false);
    }

    /** @return array<string,bool> */
    public function all(): array
    {
        return $this->grants;
    }
}
