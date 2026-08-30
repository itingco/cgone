<?php

namespace App\Models;

use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlBatch extends Model
{
    use HasFactory, ImmutableWhenPosted;

    protected $table = 'gl_batches';
    protected $guarded = [];
    protected $casts = ['posting_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (GlBatch $batch): void {
            if ($batch->business_unit_id) {
                return;
            }

            $sessionId = null;
            if (app()->bound('request') && request()->hasSession()) {
                $sessionId = (int) request()->session()->get(
                    (string) config('erp_context.business_unit_session_key', 'erp_business_unit_id')
                );
            }

            $batch->business_unit_id = $sessionId ?: BusinessUnit::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->value('id');
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'gl_batch_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
