<?php

namespace App\Application\Accounting;

use Illuminate\Support\Facades\DB;

final class DocumentNumberService
{
    public function next(int $companyId, string $documentType, string $prefix, int $year): string
    {
        return DB::transaction(function () use ($companyId, $documentType, $prefix, $year): string {
            DB::table('document_sequences')->insertOrIgnore([
                'company_id' => $companyId,
                'document_type' => $documentType,
                'prefix' => $prefix,
                'next_number' => 1,
                'padding' => 6,
                'year' => $year,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = DB::table('document_sequences')
                ->where('company_id', $companyId)
                ->where('document_type', $documentType)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            DB::table('document_sequences')->where('id', $sequence->id)->update([
                'next_number' => $sequence->next_number + 1,
                'updated_at' => now(),
            ]);

            return sprintf('%s/%d/%0'.$sequence->padding.'d', $sequence->prefix, $year, $sequence->next_number);
        });
    }
}
