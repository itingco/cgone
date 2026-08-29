<?php

namespace App\Application\Pricing;

use App\Domain\Pricing\PriceActivationPolicy;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\PriceChangeBatch;
use App\Models\PriceChangeLine;
use App\Models\PriceLevel;
use App\Models\Uom;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PriceChangeService
{
    public function __construct(private readonly PriceActivationPolicy $policy)
    {
    }

    /** @param list<array<string,mixed>> $rows */
    public function createBatch(int $companyId, int $groupId, int $userId, array $rows, ?string $filename): PriceChangeBatch
    {
        $rows = array_values(array_filter($rows, static fn (array $row): bool => collect($row)->filter(static fn ($value): bool => $value !== null && $value !== '')->isNotEmpty()));
        if ($rows === []) {
            throw new DomainException('File perubahan harga tidak memiliki baris data yang dapat diproses.');
        }

        return DB::transaction(function () use ($companyId, $groupId, $userId, $rows, $filename): PriceChangeBatch {
            $batch = PriceChangeBatch::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'batch_number' => 'PC/'.now()->format('Ymd/His').'/'.strtoupper(Str::random(4)),
                'source_filename' => $filename,
                'status' => 'pending_approval',
                'total_lines' => count($rows),
                'created_by' => $userId,
                'submitted_at' => now(),
            ]);

            foreach ($rows as $index => $row) {
                $itemCode = trim((string) ($row['item'] ?? ''));
                $uomCode = trim((string) ($row['uom'] ?? ''));
                $priceLevelCode = trim((string) ($row['price_level'] ?? ''));
                if ($itemCode === '' || $uomCode === '' || $priceLevelCode === '') {
                    throw new DomainException('Baris '.($index + 2).': item, UOM, dan price level wajib diisi.');
                }

                $item = Item::query()->where('group_id', $groupId)
                    ->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))
                    ->where('code', $itemCode)->where('is_active', true)->first();
                $uom = Uom::query()->where('group_id', $groupId)->where('code', $uomCode)->first();
                $level = PriceLevel::query()->where('company_id', $companyId)->where('code', $priceLevelCode)->where('is_active', true)->first();
                if (! $item || ! $uom || ! $level) {
                    throw new DomainException('Baris '.($index + 2).': item, UOM, atau price level tidak ditemukan.');
                }

                if (empty($row['berlaku_mulai'])) {
                    throw new DomainException('Baris '.($index + 2).': berlaku mulai wajib diisi.');
                }
                $effectiveAt = Carbon::parse($row['berlaku_mulai']);
                $this->policy->validateEffectiveAt($effectiveAt->toDateTimeImmutable(), now()->toDateTimeImmutable());
                $isValidUom = DB::table('item_uoms')->where('item_id', $item->id)->where('uom_id', $uom->id)->exists();
                if (! $isValidUom) {
                    throw new DomainException('Baris '.($index + 2).': UOM tidak terdaftar pada item tersebut.');
                }

                $newPrice = (float) ($row['harga_baru'] ?? 0);
                if ($newPrice <= 0) {
                    throw new DomainException('Baris '.($index + 2).': harga baru wajib lebih besar dari nol.');
                }

                $oldAmount = ItemPrice::query()
                    ->where('company_id', $companyId)
                    ->where('item_id', $item->id)
                    ->where('uom_id', $uom->id)
                    ->where('price_level_id', $level->id)
                    ->where('is_active', true)
                    ->value('amount');

                $batch->lines()->create([
                    'company_id' => $companyId,
                    'item_id' => $item->id,
                    'uom_id' => $uom->id,
                    'price_level_id' => $level->id,
                    'old_price' => $oldAmount,
                    'new_price' => $newPrice,
                    'effective_at' => $effectiveAt,
                    'reason' => trim((string) ($row['alasan'] ?? 'Penyesuaian harga')),
                    'status' => 'pending_approval',
                ]);
            }

            return $batch->load('lines');
        });
    }

    public function approveLine(PriceChangeLine $line, int $ceoUserId): void
    {
        DB::transaction(function () use ($line, $ceoUserId): void {
            $line = PriceChangeLine::query()->lockForUpdate()->findOrFail($line->id);
            if ($line->status !== 'pending_approval') {
                throw new DomainException('Baris harga tidak lagi menunggu approval.');
            }
            $creatorId = (int) PriceChangeBatch::query()->whereKey($line->batch_id)->value('created_by');
            if ($creatorId === $ceoUserId) {
                throw new DomainException('Pembuat pengajuan harga tidak boleh menyetujui pengajuannya sendiri.');
            }

            $line->update(['status' => 'scheduled', 'approved_by' => $ceoUserId, 'approved_at' => now()]);
            ItemPrice::query()->create([
                'company_id' => $line->company_id,
                'item_id' => $line->item_id,
                'uom_id' => $line->uom_id,
                'price_level_id' => $line->price_level_id,
                'price_change_line_id' => $line->id,
                'amount' => $line->new_price,
                'effective_at' => $line->effective_at,
                'is_active' => false,
                'approved_by' => $ceoUserId,
            ]);
            $this->refreshBatchStatus($line->batch_id);
        });
    }

    public function rejectLine(PriceChangeLine $line, int $ceoUserId, string $reason): void
    {
        DB::transaction(function () use ($line, $ceoUserId, $reason): void {
            $line = PriceChangeLine::query()->lockForUpdate()->findOrFail($line->id);
            if ($line->status !== 'pending_approval') {
                throw new DomainException('Baris harga tidak lagi menunggu approval.');
            }
            $creatorId = (int) PriceChangeBatch::query()->whereKey($line->batch_id)->value('created_by');
            if ($creatorId === $ceoUserId) {
                throw new DomainException('Pembuat pengajuan harga tidak boleh menolak pengajuannya sendiri.');
            }
            $line->update(['status' => 'rejected', 'approved_by' => $ceoUserId, 'approved_at' => now(), 'rejection_reason' => $reason]);
            $this->refreshBatchStatus($line->batch_id);
        });
    }

    public function activateDuePrices(): int
    {
        $count = 0;
        PriceChangeLine::query()
            ->where('status', 'scheduled')
            ->where('effective_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($lines) use (&$count): void {
                foreach ($lines as $line) {
                    DB::transaction(function () use ($line, &$count): void {
                        $line = PriceChangeLine::query()->lockForUpdate()->findOrFail($line->id);
                        if ($line->status !== 'scheduled' || $line->effective_at->isFuture()) {
                            return;
                        }
                        ItemPrice::query()
                            ->where('company_id', $line->company_id)
                            ->where('item_id', $line->item_id)
                            ->where('uom_id', $line->uom_id)
                            ->where('price_level_id', $line->price_level_id)
                            ->where('is_active', true)
                            ->update(['is_active' => false, 'ended_at' => $line->effective_at]);

                        ItemPrice::query()->where('price_change_line_id', $line->id)->update(['is_active' => true]);
                        $line->update(['status' => 'active']);
                        $this->refreshBatchStatus($line->batch_id);
                        $count++;
                    });
                }
            });

        return $count;
    }

    private function refreshBatchStatus(int $batchId): void
    {
        $statuses = PriceChangeLine::query()->where('batch_id', $batchId)->pluck('status');
        $status = match (true) {
            $statuses->contains('pending_approval') => 'pending_approval',
            $statuses->contains('scheduled') => 'scheduled',
            $statuses->every(static fn (string $lineStatus): bool => $lineStatus === 'rejected') => 'rejected',
            $statuses->every(static fn (string $lineStatus): bool => in_array($lineStatus, ['active', 'rejected'], true)) => 'completed',
            default => 'partially_processed',
        };

        PriceChangeBatch::query()->whereKey($batchId)->update(['status' => $status]);
    }

}
