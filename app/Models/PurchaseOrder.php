<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PurchaseOrder extends BaseModel
{
    use BelongsToCompany, ImmutableWhenPosted;
    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function lines(): HasMany { return $this->hasMany(PurchaseOrderLine::class); }
}
