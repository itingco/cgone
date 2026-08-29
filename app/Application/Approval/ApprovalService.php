<?php

namespace App\Application\Approval;

use App\Domain\Approval\ApprovalMatrix;
use App\Models\ApprovalRequest;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ApprovalService
{
    public function submit(Model $document, float $amount, int $requestedBy): ApprovalRequest
    {
        if (($document->status ?? null) !== 'draft') {
            throw new DomainException('Hanya dokumen draft yang dapat dikirim untuk approval.');
        }

        return DB::transaction(function () use ($document, $amount, $requestedBy): ApprovalRequest {
            $document->update(['status' => 'pending_approval']);
            return ApprovalRequest::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $document->company_id,
                'approvable_type' => $document::class,
                'approvable_id' => $document->getKey(),
                'amount' => $amount,
                'status' => 'pending',
                'current_step' => 1,
                'required_roles' => ApprovalMatrix::forTransaction($amount),
                'requested_by' => $requestedBy,
            ]);
        });
    }

    public function approve(ApprovalRequest $approval, User $actor): void
    {
        DB::transaction(function () use ($approval, $actor): void {
            $approval = ApprovalRequest::query()->with('approvable')->lockForUpdate()->findOrFail($approval->id);
            if ($approval->status !== 'pending') {
                throw new DomainException('Approval ini sudah selesai.');
            }
            if ($approval->requested_by === $actor->id) {
                throw new DomainException('Pembuat dokumen tidak boleh menyetujui dokumennya sendiri.');
            }

            $roles = $approval->required_roles;
            $requiredRole = $roles[$approval->current_step - 1] ?? null;
            if (! $requiredRole || ! $actor->hasRole($requiredRole, $approval->company_id)) {
                throw new DomainException("Tahap ini hanya dapat disetujui oleh {$requiredRole}.");
            }

            $approval->actions()->create([
                'step' => $approval->current_step,
                'role_code' => $requiredRole,
                'action' => 'approved',
                'acted_by' => $actor->id,
                'acted_at' => now(),
            ]);

            if ($approval->current_step >= count($roles)) {
                $approval->update(['status' => 'approved', 'completed_at' => now()]);
                $approval->approvable->update(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()]);
            } else {
                $approval->increment('current_step');
            }
        });
    }

    public function reject(ApprovalRequest $approval, User $actor, string $reason): void
    {
        DB::transaction(function () use ($approval, $actor, $reason): void {
            $approval = ApprovalRequest::query()->with('approvable')->lockForUpdate()->findOrFail($approval->id);
            if ($approval->status !== 'pending') {
                throw new DomainException('Approval ini sudah selesai.');
            }
            if ($approval->requested_by === $actor->id) {
                throw new DomainException('Pembuat dokumen tidak boleh menolak dokumennya sendiri.');
            }
            $requiredRole = $approval->required_roles[$approval->current_step - 1] ?? null;
            if (! $requiredRole || ! $actor->hasRole($requiredRole, $approval->company_id)) {
                throw new DomainException("Tahap ini hanya dapat ditolak oleh {$requiredRole}.");
            }

            $approval->actions()->create([
                'step' => $approval->current_step,
                'role_code' => $requiredRole,
                'action' => 'rejected',
                'comment' => $reason,
                'acted_by' => $actor->id,
                'acted_at' => now(),
            ]);
            $approval->update(['status' => 'rejected', 'completed_at' => now()]);
            $approval->approvable->update(['status' => 'rejected']);
        });
    }
}
