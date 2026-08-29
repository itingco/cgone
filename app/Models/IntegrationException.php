<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class IntegrationException extends BaseModel
{
    use BelongsToCompany;
    protected function casts(): array { return [...parent::casts(), 'context' => 'array', 'resolved_at' => 'datetime']; }
}
