<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IntegrationDocument extends BaseModel
{
    use BelongsToCompany;
    protected function casts(): array
    {
        return [...parent::casts(), 'document_date' => 'date', 'posted_at' => 'datetime', 'payload_hash_data' => 'array'];
    }

    public function lines(): HasMany { return $this->hasMany(IntegrationLine::class); }
    public function source(): BelongsTo { return $this->belongsTo(IntegrationSource::class, 'integration_source_id'); }
}
