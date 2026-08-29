<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class JournalAdjustmentLine extends BaseModel
{
    protected function casts(): array { return [...parent::casts(), 'dimensions' => 'array']; }
    public function adjustment(): BelongsTo { return $this->belongsTo(JournalAdjustment::class, 'journal_adjustment_id'); }
}
