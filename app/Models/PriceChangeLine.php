<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PriceChangeLine extends BaseModel
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [...parent::casts(), 'effective_at' => 'datetime', 'approved_at' => 'datetime'];
    }

    public function batch(): BelongsTo { return $this->belongsTo(PriceChangeBatch::class, 'batch_id'); }
    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function uom(): BelongsTo { return $this->belongsTo(Uom::class); }
    public function priceLevel(): BelongsTo { return $this->belongsTo(PriceLevel::class); }
}
