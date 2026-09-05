<?php

namespace App\Models\Configuration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class TransactionTemplate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'payment_term_days' => 'integer',
    ];

    public function documentTypes(): HasMany
    {
        return $this->hasMany(TransactionTemplateDocumentType::class);
    }

    public function businessUnit(): BelongsTo { return $this->belongsTo(\App\Models\BusinessUnit::class); }
    public function location(): BelongsTo { return $this->belongsTo(\App\Models\Location::class); }
    public function bin(): BelongsTo { return $this->belongsTo(\App\Models\LocationBin::class, 'bin_id'); }
    public function priceLevel(): BelongsTo { return $this->belongsTo(\App\Models\PriceLevel::class); }
    public function taxPostingGroup(): BelongsTo { return $this->belongsTo(\App\Models\TaxPostingGroup::class); }

    public function appliesTo(string $documentType): bool
    {
        if ($this->relationLoaded('documentTypes')) {
            return $this->documentTypes->contains('document_type', $documentType);
        }

        return $this->documentTypes()->where('document_type', $documentType)->exists();
    }

    public function defaults(): array
    {
        return [
            'business_unit_id' => $this->business_unit_id,
            'location_id' => $this->location_id,
            'bin_id' => $this->bin_id,
            'price_level_id' => $this->price_level_id,
            'currency_code' => $this->currency_code,
            'payment_term_days' => $this->payment_term_days,
            'tax_posting_group_id' => $this->tax_posting_group_id,
            'number_series_code' => $this->number_series_code,
            'notes' => $this->default_notes,
        ];
    }
}
