<?php

namespace App\Application\Posting;

use App\Application\Posting\Handlers\GoodsReceiptPostingHandler;
use App\Application\Posting\Handlers\SupplierAdvancePostingHandler;
use App\Application\Posting\Handlers\SupplierInvoicePostingHandler;
use App\Application\Posting\Handlers\SupplierPaymentPostingHandler;
use App\Application\Posting\Handlers\JournalAdjustmentPostingHandler;
use App\Application\Posting\Handlers\InventoryAdjustmentPostingHandler;
use DomainException;
use Illuminate\Support\Facades\DB;

final class PostingEngine
{
    public function post(string $documentType, int $documentId, int $userId): array
    {
        return DB::transaction(function () use ($documentType, $documentId, $userId): array {
            $handler = match ($documentType) {
                'goods_receipt' => app(GoodsReceiptPostingHandler::class),
                'supplier_advance' => app(SupplierAdvancePostingHandler::class),
                'supplier_invoice' => app(SupplierInvoicePostingHandler::class),
                'supplier_payment' => app(SupplierPaymentPostingHandler::class),
                'journal_adjustment' => app(JournalAdjustmentPostingHandler::class),
                'inventory_adjustment' => app(InventoryAdjustmentPostingHandler::class),
                default => throw new DomainException("Jenis posting {$documentType} belum didukung."),
            };

            $result = $handler->post($documentId, $userId);
            if (empty($result['company_id'])) {
                throw new DomainException('Posting handler wajib mengembalikan company_id untuk audit tenant.');
            }

            DB::table('audit_logs')->insert([
                'company_id' => (int) ($result['company_id'] ?? 0),
                'user_id' => $userId,
                'event' => 'posted',
                'auditable_type' => $documentType,
                'auditable_id' => $documentId,
                'after_data' => json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'after_hash' => hash('sha256', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                'ip_address' => app()->runningInConsole() ? null : request()->ip(),
                'user_agent' => app()->runningInConsole() ? null : request()->userAgent(),
                'created_at' => now(),
            ]);
            return $result;
        }, attempts: 3);
    }
}
