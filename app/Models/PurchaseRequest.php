<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseRequest extends BaseModel
{
    use BelongsToCompany, ImmutableWhenPosted;
    public function lines(): HasMany { return $this->hasMany(PurchaseRequestLine::class); }
}
