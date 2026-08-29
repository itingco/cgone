<?php

namespace App\Http\Controllers;

use App\Application\Accounting\DocumentNumberService;
use App\Application\Approval\ApprovalService;
use App\Domain\Accounting\BalancedJournal;
use App\Domain\Accounting\JournalReversalBuilder;
use App\Models\Account;
use App\Models\ApprovalRequest;
use App\Models\InventoryAdjustment;
use App\Models\Item;
use App\Models\JournalAdjustment;
use App\Models\JournalEntry;
use App\Models\Uom;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $groupId = (int) DB::table('companies')->where('id', $companyId)->value('group_id');

        return view('adjustments.index', [
            'journalAdjustments' => JournalAdjustment::query()->forCompany($companyId)->with('sourceJournal')->latest()->limit(30)->get(),
            'inventoryAdjustments' => InventoryAdjustment::query()->forCompany($companyId)->latest()->limit(30)->get(),
            'journals' => JournalEntry::query()->forCompany($companyId)->where('status', 'posted')->latest('journal_date')->limit(50)->get(),
            'accounts' => Account::query()->forCompany($companyId)->where('is_active', true)->orderBy('code')->get(),
            'warehouses' => Warehouse::query()->forCompany($companyId)->where('is_active', true)->orderBy('name')->get(),
            'items' => Item::query()->where('group_id', $groupId)->where(fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId))->where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::query()->where('group_id', $groupId)->orderBy('code')->get(),
            'approvals' => ApprovalRequest::query()->forCompany($companyId)
                ->where('status', 'pending')
                ->whereIn('approvable_type', [JournalAdjustment::class, InventoryAdjustment::class])
                ->latest()->get(),
        ]);
    }

    public function storeJournal(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'document_date' => ['required', 'date'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'account_id' => ['required', 'array', 'min:2'],
            'account_id.*' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['required', 'array'],
            'description.*' => ['nullable', 'string', 'max:255'],
            'debit' => ['required', 'array'],
            'debit.*' => ['nullable', 'numeric', 'gte:0'],
            'credit' => ['required', 'array'],
            'credit.*' => ['nullable', 'numeric', 'gte:0'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $lines = [];
        foreach ($data['account_id'] as $index => $accountId) {
            $debit = round((float) ($data['debit'][$index] ?? 0));
            $credit = round((float) ($data['credit'][$index] ?? 0));
            if ($debit <= 0 && $credit <= 0) continue;
            if ($debit > 0 && $credit > 0) {
                throw ValidationException::withMessages(['debit' => 'Satu baris hanya boleh berisi debit atau kredit.']);
            }
            abort_unless(Account::query()->forCompany($companyId)->whereKey($accountId)->exists(), 422, 'Akun tidak tersedia pada perusahaan aktif.');
            $lines[] = ['account_id' => (int) $accountId, 'description' => $data['description'][$index] ?? null, 'debit' => (int) $debit, 'credit' => (int) $credit];
        }
        if (count($lines) < 2) {
            throw ValidationException::withMessages(['account_id' => 'Minimal dua baris jurnal bernilai wajib diisi.']);
        }
        $balanced = new BalancedJournal(array_map(static fn (array $line): array => ['debit' => $line['debit'], 'credit' => $line['credit']], $lines));

        $adjustment = DB::transaction(function () use ($data, $companyId, $request, $numbers, $lines, $balanced): JournalAdjustment {
            $document = JournalAdjustment::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'document_number' => $numbers->next($companyId, 'journal_adjustment', 'JA', (int) date('Y', strtotime($data['document_date']))),
                'document_date' => $data['document_date'],
                'adjustment_type' => 'manual',
                'reason' => $data['reason'],
                'status' => 'draft',
                'total_amount' => $balanced->totalDebit(),
                'created_by' => $request->user()->id,
            ]);
            foreach ($lines as $line) $document->lines()->create($line);
            return $document;
        });
        $approvals->submit($adjustment, (float) $adjustment->total_amount, $request->user()->id);
        return back()->with('success', "Journal adjustment {$adjustment->document_number} dikirim untuk approval.");
    }

    public function storeReversal(Request $request, DocumentNumberService $numbers, ApprovalService $approvals, JournalReversalBuilder $builder): RedirectResponse
    {
        $data = $request->validate([
            'source_journal_entry_id' => ['required', 'integer', 'exists:journal_entries,id'],
            'document_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        $source = JournalEntry::query()->forCompany($companyId)->with('lines')->where('status', 'posted')->findOrFail($data['source_journal_entry_id']);
        $alreadyExists = JournalAdjustment::query()->forCompany($companyId)
            ->where('source_journal_entry_id', $source->id)
            ->whereNotIn('status', ['rejected'])
            ->exists();
        abort_if($alreadyExists, 422, 'Jurnal ini sudah memiliki pengajuan reversal aktif atau posted.');

        $reversalLines = $builder->build($source->lines->map(static fn ($line): array => [
            'account_id' => (int) $line->account_id,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'description' => $line->description,
        ])->all());

        $adjustment = DB::transaction(function () use ($data, $companyId, $request, $numbers, $source, $reversalLines): JournalAdjustment {
            $document = JournalAdjustment::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'branch_id' => $source->branch_id,
                'source_journal_entry_id' => $source->id,
                'document_number' => $numbers->next($companyId, 'journal_reversal', 'JR', (int) date('Y', strtotime($data['document_date']))),
                'document_date' => $data['document_date'],
                'adjustment_type' => 'reversal',
                'reason' => $data['reason'],
                'status' => 'draft',
                'total_amount' => array_sum(array_column($reversalLines, 'debit')),
                'created_by' => $request->user()->id,
            ]);
            foreach ($reversalLines as $line) $document->lines()->create($line);
            return $document;
        });
        $approvals->submit($adjustment, (float) $adjustment->total_amount, $request->user()->id);
        return back()->with('success', "Reversal {$adjustment->document_number} dibuat tanpa mengubah jurnal asal.");
    }

    public function storeInventory(Request $request, DocumentNumberService $numbers, ApprovalService $approvals): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'document_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'uom_id' => ['required', 'integer', 'exists:uoms,id'],
            'quantity_delta' => ['required', 'numeric', 'not_in:0'],
            'unit_cost' => ['required', 'numeric', 'gt:0'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'expiry_date' => ['nullable', 'date'],
        ]);
        $companyId = (int) $request->session()->get('company_id');
        abort_unless(Warehouse::query()->forCompany($companyId)->whereKey($data['warehouse_id'])->exists(), 422, 'Gudang tidak tersedia pada perusahaan aktif.');
        abort_unless(DB::table('item_uoms')->where('item_id', $data['item_id'])->where('uom_id', $data['uom_id'])->exists(), 422, 'UOM tidak terdaftar pada item.');
        $item = Item::query()->findOrFail($data['item_id']);
        if ($item->track_batch && empty($data['batch_number'])) throw ValidationException::withMessages(['batch_number' => 'Nomor batch wajib diisi untuk item ini.']);
        if ($item->track_serial && empty($data['serial_number'])) throw ValidationException::withMessages(['serial_number' => 'Nomor serial wajib diisi untuk item ini.']);

        $estimatedAmount = abs((float) $data['quantity_delta'] * (float) $data['unit_cost']);
        $adjustment = DB::transaction(function () use ($data, $companyId, $request, $numbers, $estimatedAmount): InventoryAdjustment {
            $document = InventoryAdjustment::query()->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'warehouse_id' => $data['warehouse_id'],
                'document_number' => $numbers->next($companyId, 'inventory_adjustment', 'IA', (int) date('Y', strtotime($data['document_date']))),
                'document_date' => $data['document_date'],
                'reason' => $data['reason'],
                'status' => 'draft',
                'total_amount' => $estimatedAmount,
                'created_by' => $request->user()->id,
            ]);
            $document->lines()->create([
                'item_id' => $data['item_id'], 'uom_id' => $data['uom_id'],
                'quantity_delta' => $data['quantity_delta'], 'unit_cost' => $data['unit_cost'],
                'batch_number' => $data['batch_number'] ?? null, 'serial_number' => $data['serial_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
            ]);
            return $document;
        });
        $approvals->submit($adjustment, $estimatedAmount, $request->user()->id);
        return back()->with('success', "Inventory adjustment {$adjustment->document_number} dikirim untuk approval.");
    }
}
