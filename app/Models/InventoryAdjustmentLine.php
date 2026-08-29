<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryAdjustmentLine extends BaseModel
{
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function adjustment(): BelongsTo { return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id'); }
}
