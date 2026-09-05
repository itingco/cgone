<?php

namespace App\Models\HumanCapital;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Employee extends Model
{
    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
        'original_join_date' => 'date',
        'rejoin_date' => 'date',
        'termination_date' => 'date',
        'tax_registration_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(EmployeeAllocation::class)->orderByDesc('effective_from');
    }

    public function allocationAt(CarbonInterface|string $date): ?EmployeeAllocation
    {
        $value = $date instanceof CarbonInterface ? $date->toDateString() : (string) $date;

        return $this->allocations()
            ->whereDate('effective_from', '<=', $value)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $value))
            ->orderByDesc('effective_from')
            ->first();
    }
}
