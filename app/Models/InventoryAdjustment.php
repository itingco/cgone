<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryAdjustment extends BaseModel
{
    use BelongsToCompany;
    public function lines(): HasMany { return $this->hasMany(InventoryAdjustmentLine::class); }
}
