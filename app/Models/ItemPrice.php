<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class ItemPrice extends BaseModel
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [...parent::casts(), 'effective_at' => 'datetime', 'ended_at' => 'datetime', 'is_active' => 'boolean'];
    }
}
