<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\ImmutableWhenPosted;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SupplierPayment extends BaseModel
{
    use BelongsToCompany, ImmutableWhenPosted;
    public function invoice(): BelongsTo { return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id'); }
}
