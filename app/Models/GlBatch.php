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

    public function entries(): HasMany
    {
        return $this->hasMany(GlEntry::class, 'gl_batch_id');
    }

    public function businessUnit(): BelongsTo
    {
        return $this->belongsTo(BusinessUnit::class);
    }
}
