<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class Supplier extends BaseModel
{
    use BelongsToCompany;
}
