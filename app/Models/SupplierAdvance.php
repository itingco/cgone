<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierAdvance extends BaseModel
{
    use BelongsToCompany, ImmutableWhenPosted;
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
}
