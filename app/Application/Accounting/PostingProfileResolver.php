<?php

namespace App\Application\Accounting;

use DomainException;
use Illuminate\Support\Facades\DB;

final class PostingProfileResolver
{
    public function debitAccount(int $companyId, string $transactionType, string $postingKey): int
    {
        return $this->resolve($companyId, $transactionType, $postingKey, 'debit_account_id');
    }

    public function creditAccount(int $companyId, string $transactionType, string $postingKey): int
    {
        return $this->resolve($companyId, $transactionType, $postingKey, 'credit_account_id');
    }

    private function resolve(int $companyId, string $transactionType, string $postingKey, string $column): int
    {
        $id = DB::table('posting_profiles')
            ->where('company_id', $companyId)
            ->where('transaction_type', $transactionType)
            ->where('posting_key', $postingKey)
            ->where('is_active', true)
            ->value($column);

        if (! $id) {
            throw new DomainException("Posting profile {$transactionType}/{$postingKey} belum lengkap.");
        }

        return (int) $id;
    }
}
