<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseOrderLine extends BaseModel
{
    public function purchaseOrder(): BelongsTo { return $this->belongsTo(PurchaseOrder::class); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
