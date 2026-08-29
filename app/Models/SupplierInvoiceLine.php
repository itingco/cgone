<?php

namespace App\Models;

final class SupplierInvoiceLine extends BaseModel
{
    protected function casts(): array
    {
        return [...parent::casts(), 'matching_result' => 'array'];
    }
}
