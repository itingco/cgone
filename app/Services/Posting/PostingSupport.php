<?php

namespace App\Services\Posting;

use App\Models\Documents\OperationalDocument;

final class PostingSupport
{
    public static function header(OperationalDocument $doc, string $postedNo, int $userId): array
    {
        return [
            'document_no' => $postedNo,
            'document_date' => $doc->document_date,
            'source_document_no' => $doc->document_no,
            'business_unit_id' => $doc->business_unit_id,
            'currency_code' => $doc->currency_code,
            'subtotal' => $doc->subtotal,
            'discount_total' => $doc->discount_total,
            'tax_total' => $doc->tax_total,
            'grand_total' => $doc->grand_total,
            'notes' => $doc->notes,
            'posted_by' => $userId,
            'posted_at' => now(),
        ];
    }

    public static function add(array &$lines, int $accountId, float $debit, float $credit, string $description): void
    {
        if (abs($debit) < 0.00001 && abs($credit) < 0.00001) {
            return;
        }

        $key = (string) $accountId;
        if (! isset($lines[$key])) {
            $lines[$key] = [
                'account_id' => $accountId,
                'debit' => 0.0,
                'credit' => 0.0,
                'description' => $description,
            ];
        }

        $lines[$key]['debit'] += (float) $debit;
        $lines[$key]['credit'] += (float) $credit;
    }

    public static function normalized(array $lines): array
    {
        return array_values(array_map(function ($line) {
            $debit = $line['debit'];
            $credit = $line['credit'];

            if ($debit > $credit) {
                $line['debit'] = round($debit - $credit, 4);
                $line['credit'] = 0;
            } elseif ($credit > $debit) {
                $line['credit'] = round($credit - $debit, 4);
                $line['debit'] = 0;
            } else {
                $line['debit'] = 0;
                $line['credit'] = 0;
            }

            return $line;
        }, array_filter($lines, fn ($line) => abs($line['debit'] - $line['credit']) > 0.00001)));
    }
}
