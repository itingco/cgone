<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class IntegrationSource extends BaseModel
{
    use BelongsToCompany;
}
