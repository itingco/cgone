<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JournalAdjustment extends BaseModel
{
    use BelongsToCompany;

    public function lines(): HasMany { return $this->hasMany(JournalAdjustmentLine::class); }
    public function sourceJournal(): BelongsTo { return $this->belongsTo(JournalEntry::class, 'source_journal_entry_id'); }
}
