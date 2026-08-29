<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;

final class Account extends BaseModel
{
    use BelongsToCompany;
}
