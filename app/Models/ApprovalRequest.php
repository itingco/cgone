<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ApprovalRequest extends BaseModel
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [...parent::casts(), 'required_roles' => 'array', 'completed_at' => 'datetime'];
    }

    public function approvable(): MorphTo { return $this->morphTo(); }
    public function actions(): HasMany { return $this->hasMany(ApprovalAction::class); }
}
