<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PurchaseRequestLine extends BaseModel
{
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function uom(): BelongsTo { return $this->belongsTo(Uom::class); }
}
