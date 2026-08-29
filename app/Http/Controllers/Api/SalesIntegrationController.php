<?php

namespace App\Http\Controllers\Api;

use App\Application\Integration\SalesImportService;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\IntegrationSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SalesIntegrationController extends Controller
{
    public function store(Request $request, SalesImportService $service): JsonResponse
    {
        $payload = $request->validate([
            'company_code' => ['required', 'string'],
            'source_code' => ['required', 'string'],
            'nomor_dokumen' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'jenis_dokumen' => ['nullable', 'string'],
            'customer_code' => ['required', 'string'],
            'branch_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'metode_pembayaran' => ['nullable', 'string'],
            'subtotal' => ['required', 'numeric'],
            'pajak' => ['nullable', 'numeric'],
            'total' => ['required', 'numeric'],
            'baris' => ['required', 'array', 'min:1'],
            'baris.*.item_code' => ['required', 'string'],
            'baris.*.uom_code' => ['required', 'string'],
            'baris.*.qty' => ['required', 'numeric', 'gt:0'],
            'baris.*.harga' => ['required', 'numeric', 'gte:0'],
            'baris.*.diskon' => ['nullable', 'numeric', 'gte:0'],
            'baris.*.pajak' => ['nullable', 'numeric', 'gte:0'],
            'baris.*.total' => ['required', 'numeric'],
        ]);

        $company = Company::query()->where('code', $payload['company_code'])->firstOrFail();
        DB::statement("SELECT set_config('app.company_id', ?, false)", [(string) $company->id]);
        $source = IntegrationSource::query()->where('company_id', $company->id)->where('code', $payload['source_code'])->firstOrFail();
        $token = (string) $request->bearerToken();
        abort_unless($source->api_token_hash && hash_equals($source->api_token_hash, hash('sha256', $token)), 401, 'Token integrasi tidak valid.');

        $document = $service->import($company->id, $payload['source_code'], $payload);
        return response()->json([
            'message' => $document->status === 'exception' ? 'Dokumen diterima dengan exception.' : 'Dokumen berhasil diterima.',
            'uuid' => $document->uuid,
            'status' => $document->status,
        ], $document->wasRecentlyCreated ? 201 : 200);
    }
}
