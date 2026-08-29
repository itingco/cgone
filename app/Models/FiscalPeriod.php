<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class FiscalPeriod extends BaseModel
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [...parent::casts(), 'start_date' => 'date', 'end_date' => 'date', 'closed_at' => 'datetime'];
    }
}
