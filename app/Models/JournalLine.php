<?php

namespace App\Models;

final class JournalLine extends BaseModel
{
    protected function casts(): array
    {
        return [...parent::casts(), 'dimensions' => 'array'];
    }
}
