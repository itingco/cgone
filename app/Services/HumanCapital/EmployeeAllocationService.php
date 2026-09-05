<?php

namespace App\Services\HumanCapital;

use App\Models\HumanCapital\EmployeeAllocation;
use DomainException;

final class EmployeeAllocationService
{
    public function assertNoOverlap(int $employeeId, string $from, ?string $to, ?int $ignoreId = null): void
    {
        EffectiveDateRange::assertValid($from, $to);
        $query = EmployeeAllocation::query()->where('employee_id', $employeeId);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $infinity = '9999-12-31';
        $overlap = $query
            ->whereDate('effective_from', '<=', $to ?: $infinity)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from))
            ->exists();

        if ($overlap) {
            throw new DomainException('Employee allocation period overlaps an existing allocation.');
        }
    }
}
