<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

final class VendorLedgerApplication extends Model
{
    protected $guarded = [];

    protected $casts = [
        'applied_amount' => 'decimal:4',
        'applied_at' => 'datetime',
    ];

    public function sourceDebit()
    {
        return $this->belongsTo(\App\Models\VendorLedger::class, 'source_debit_ledger_id');
    }

    public function targetCredit()
    {
        return $this->belongsTo(\App\Models\VendorLedger::class, 'target_credit_ledger_id');
    }
}
