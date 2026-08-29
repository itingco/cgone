<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Branch extends BaseModel
{
    use BelongsToCompany;
    public function warehouses(): HasMany { return $this->hasMany(Warehouse::class); }
}
