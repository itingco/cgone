<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\DatabaseContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

final class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()]);
    }

    public function update(Request $request, DatabaseContext $databases): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $failures = $this->syncUserFieldAcrossDatabases(
            $request,
            $databases,
            ['name' => $data['name'], 'updated_at' => now()]
        );

        if ($failures) {
            return back()->with('success', 'Nama berhasil diperbarui pada database yang dapat diakses.')
                ->withErrors(['profile' => 'Tidak dapat sinkron ke: '.implode(', ', $failures)]);
        }

        return back()->with('success', 'Profile berhasil disinkronkan ke seluruh database.');
    }

    public function updatePassword(Request $request, DatabaseContext $databases): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $hash = Hash::make($data['password']);
        $failures = $this->syncUserFieldAcrossDatabases(
            $request,
            $databases,
            ['password' => $hash, 'updated_at' => now()]
        );

        $request->session()->regenerate();

        if ($failures) {
            return back()->with('success', 'Password berhasil diganti pada database yang dapat diakses.')
                ->withErrors(['password' => 'Tidak dapat sinkron ke: '.implode(', ', $failures)]);
        }

        return back()->with('success', 'Password berhasil diganti dan disinkronkan ke seluruh database.');
    }

    public function documents(Request $request): View
    {
        $definitions = [
            'posted_shipments' => ['label' => 'Posted Shipment', 'type' => 'shipment'],
            'posted_sales_invoices' => ['label' => 'Posted Sales Invoice', 'type' => 'sales-invoice'],
            'posted_receipts' => ['label' => 'Posted Receipt', 'type' => 'receipt'],
            'posted_purchase_invoices' => ['label' => 'Posted Purchase Invoice', 'type' => 'purchase-invoice'],
        ];

        $documents = collect();
        foreach ($definitions as $table => $meta) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $rows = DB::table($table)
                ->where('posted_by', $request->user()->id)
                ->orderByDesc('posted_at')
                ->limit(100)
                ->get(['id', 'document_no', 'document_date', 'source_document_no', 'grand_total', 'posted_at']);

            foreach ($rows as $row) {
                $documents->push((object) [
                    'id' => $row->id,
                    'label' => $meta['label'],
                    'type' => $meta['type'],
                    'document_no' => $row->document_no,
                    'document_date' => $row->document_date,
                    'source_document_no' => $row->source_document_no,
                    'grand_total' => $row->grand_total,
                    'posted_at' => $row->posted_at,
                ]);
            }
        }

        return view('profile.documents', [
            'user' => $request->user(),
            'documents' => $documents->sortByDesc('posted_at')->take(200)->values(),
        ]);
    }

    /** @return array<int,string> */
    private function syncUserFieldAcrossDatabases(Request $request, DatabaseContext $databases, array $values): array
    {
        $sessionKey = (string) config('erp_context.session_key', 'erp_database');
        $original = $databases->resolve((string) $request->session()->get($sessionKey));
        $email = (string) $request->user()->email;
        $failures = [];

        try {
            foreach ($databases->available() as $database => $label) {
                try {
                    $databases->activate($database);
                    $databases->ping();
                    if (! Schema::connection($databases->connectionName())->hasTable('users')) {
                        continue;
                    }
                    DB::connection($databases->connectionName())
                        ->table('users')
                        ->where('email', $email)
                        ->update($values);
                } catch (Throwable) {
                    $failures[] = $label.' ['.$database.']';
                }
            }
        } finally {
            if ($original !== '' && $databases->isAllowed($original)) {
                $databases->activate($original);
            }
        }

        return $failures;
    }
}
