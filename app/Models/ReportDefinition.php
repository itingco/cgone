<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class ReportDefinition extends BaseModel
{
    use BelongsToCompany;
    protected function casts(): array { return [...parent::casts(), 'filters' => 'array', 'columns' => 'array', 'grouping' => 'array', 'sorting' => 'array']; }
}
