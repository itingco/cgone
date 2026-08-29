<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class GoodsReceiptLine extends BaseModel
{
    public function receipt(): BelongsTo { return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id'); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
}
