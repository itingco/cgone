<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

final class Group extends BaseModel
{
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
