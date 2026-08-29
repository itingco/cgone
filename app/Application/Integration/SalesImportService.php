<?php

namespace App\Application\Integration;

use App\Models\Customer;
use App\Models\IntegrationDocument;
use App\Models\IntegrationException;
use App\Models\IntegrationSource;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\Uom;
use DomainException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

final class SalesImportService
{
    /** @param array<string,mixed> $payload */
    public function import(int $companyId, string $sourceCode, array $payload): IntegrationDocument
    {
        return DB::transaction(function () use ($companyId, $sourceCode, $payload): IntegrationDocument {
            $source = IntegrationSource::query()->where('company_id', $companyId)->where('code', $sourceCode)->where('is_active', true)->firstOrFail();
            $externalNumber = trim((string) ($payload['nomor_dokumen'] ?? ''));
            if ($externalNumber === '') {
                throw new DomainException('nomor_dokumen wajib diisi.');
            }

            $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $existing = IntegrationDocument::query()
                ->where('integration_source_id', $source->id)
                ->where('external_document_number', $externalNumber)
                ->first();
            if ($existing) {
                if (! hash_equals($existing->payload_hash, $hash)) {
                    throw new DomainException('Nomor dokumen sudah pernah diterima dengan isi berbeda.');
                }
                return $existing;
            }

            $document = IntegrationDocument::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'integration_source_id' => $source->id,
                'branch_id' => $payload['branch_id'] ?? null,
                'warehouse_id' => $payload['warehouse_id'] ?? null,
                'external_document_number' => $externalNumber,
                'document_date' => $payload['tanggal'],
                'document_type' => $payload['jenis_dokumen'] ?? 'sales_invoice',
                'status' => 'validating',
                'customer_code' => $payload['customer_code'] ?? null,
                'payment_method' => $payload['metode_pembayaran'] ?? null,
                'subtotal' => $payload['subtotal'] ?? 0,
                'tax_amount' => $payload['pajak'] ?? 0,
                'total_amount' => $payload['total'] ?? 0,
                'payload_hash_data' => ['schema_version' => $payload['schema_version'] ?? 1],
                'payload_hash' => $hash,
            ]);

            $company = DB::table('companies')->where('id', $companyId)->first(['id', 'group_id']);
            $customer = Customer::query()->where('company_id', $companyId)->where('code', $payload['customer_code'])->where('is_active', true)->first();
            $documentDate = Carbon::parse($payload['tanggal']);
            $errors = [];

            if (! $customer || ! $customer->price_level_id) {
                $errors[] = ['line' => 0, 'message' => 'Customer aktif atau price level customer tidak ditemukan.'];
            }
            if (! empty($payload['branch_id']) && ! DB::table('branches')->where('company_id', $companyId)->where('id', $payload['branch_id'])->exists()) {
                $errors[] = ['line' => 0, 'message' => 'Cabang tidak terdaftar pada perusahaan aktif.'];
            }
            if (! empty($payload['warehouse_id']) && ! DB::table('warehouses')->where('company_id', $companyId)->where('id', $payload['warehouse_id'])->exists()) {
                $errors[] = ['line' => 0, 'message' => 'Gudang tidak terdaftar pada perusahaan aktif.'];
            }

            $calculatedSubtotal = 0.0;
            $calculatedTax = 0.0;
            $calculatedTotal = 0.0;
            foreach (($payload['baris'] ?? []) as $index => $row) {
                $item = Item::query()
                    ->where('group_id', $company->group_id)
                    ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))
                    ->where('code', $row['item_code'] ?? null)
                    ->where('is_active', true)
                    ->first();
                $uom = Uom::query()->where('group_id', $company->group_id)->where('code', $row['uom_code'] ?? null)->first();
                if (! $item || ! $uom) {
                    $errors[] = ['line' => $index + 1, 'message' => 'Item atau UOM tidak ditemukan.'];
                } elseif (! DB::table('item_uoms')->where('item_id', $item->id)->where('uom_id', $uom->id)->exists()) {
                    $errors[] = ['line' => $index + 1, 'message' => 'UOM tidak terdaftar pada item.'];
                } elseif ($customer) {
                    $price = ItemPrice::query()
                        ->where('company_id', $companyId)
                        ->where('item_id', $item->id)
                        ->where('uom_id', $uom->id)
                        ->where('price_level_id', $customer->price_level_id)
                        ->where('effective_at', '<=', $documentDate)
                        ->where(fn ($query) => $query->whereNull('ended_at')->orWhere('ended_at', '>', $documentDate))
                        ->orderByDesc('effective_at')
                        ->first();
                    if (! $price) {
                        $errors[] = ['line' => $index + 1, 'message' => 'Harga price level customer tidak tersedia pada tanggal transaksi.'];
                    } elseif (abs((float) $price->amount - (float) ($row['harga'] ?? 0)) > 0.01) {
                        $errors[] = ['line' => $index + 1, 'message' => 'Harga transaksi berbeda dari harga ERP yang disetujui.'];
                    }
                }

                $calculatedSubtotal += ((float) ($row['qty'] ?? 0) * (float) ($row['harga'] ?? 0)) - (float) ($row['diskon'] ?? 0);
                $calculatedTax += (float) ($row['pajak'] ?? 0);
                $calculatedTotal += (float) ($row['total'] ?? 0);

                $document->lines()->create([
                    'line_number' => $index + 1,
                    'item_code' => $row['item_code'] ?? '',
                    'uom_code' => $row['uom_code'] ?? '',
                    'quantity' => $row['qty'] ?? 0,
                    'unit_price' => $row['harga'] ?? 0,
                    'discount_amount' => $row['diskon'] ?? 0,
                    'tax_amount' => $row['pajak'] ?? 0,
                    'line_total' => $row['total'] ?? 0,
                    'raw_payload' => $row,
                ]);
            }

            if (abs($calculatedSubtotal - (float) ($payload['subtotal'] ?? 0)) > 0.01) {
                $errors[] = ['line' => 0, 'message' => 'Subtotal header tidak sama dengan perhitungan detail.'];
            }
            if (abs($calculatedTax - (float) ($payload['pajak'] ?? 0)) > 0.01) {
                $errors[] = ['line' => 0, 'message' => 'Pajak header tidak sama dengan detail.'];
            }
            if (abs($calculatedTotal - (float) ($payload['total'] ?? 0)) > 0.01) {
                $errors[] = ['line' => 0, 'message' => 'Total header tidak sama dengan detail.'];
            }
            if (abs(((float) ($payload['subtotal'] ?? 0) + (float) ($payload['pajak'] ?? 0)) - (float) ($payload['total'] ?? 0)) > 0.01) {
                $errors[] = ['line' => 0, 'message' => 'Total dokumen harus sama dengan subtotal ditambah pajak.'];
            }

            if ($errors !== []) {
                foreach ($errors as $error) {
                    IntegrationException::query()->create([
                        'company_id' => $companyId,
                        'integration_document_id' => $document->id,
                        'error_code' => 'VALIDATION_FAILED',
                        'message' => $error['message'],
                        'context' => ['line' => $error['line']],
                        'status' => 'open',
                    ]);
                }
                $document->update(['status' => 'exception']);
            } else {
                $document->update(['status' => $source->auto_post ? 'ready_to_post' : 'validated']);
            }

            return $document->fresh('lines');
        });
    }
}
