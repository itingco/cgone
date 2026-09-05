<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Model;

final class CustomerLedgerApplication extends Model
{
    protected $guarded = [];

    protected $casts = [
        'applied_amount' => 'decimal:4',
        'applied_at' => 'datetime',
    ];

    public function sourceCredit()
    {
        return $this->belongsTo(\App\Models\CustomerLedger::class, 'source_credit_ledger_id');
    }

    public function targetDebit()
    {
        return $this->belongsTo(\App\Models\CustomerLedger::class, 'target_debit_ledger_id');
    }
}
