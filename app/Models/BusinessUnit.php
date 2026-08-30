<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class BusinessUnit extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_active', 'is_default'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function glBatches(): HasMany
    {
        return $this->hasMany(GlBatch::class);
    }
}
