<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Company extends BaseModel
{
    public function group(): BelongsTo { return $this->belongsTo(Group::class); }
    public function branches(): HasMany { return $this->hasMany(Branch::class); }
    public function users(): BelongsToMany { return $this->belongsToMany(User::class); }
}
