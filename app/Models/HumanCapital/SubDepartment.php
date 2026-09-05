<?php

namespace App\Models\HumanCapital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SubDepartment extends Model
{
    protected $table = 'sub_departments';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
