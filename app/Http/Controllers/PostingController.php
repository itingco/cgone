<?php

namespace App\Http\Controllers;

use App\Application\Posting\PostingEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PostingController extends Controller
{
    public function store(Request $request, PostingEngine $engine, string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['goods_receipt', 'supplier_advance', 'supplier_invoice', 'supplier_payment', 'journal_adjustment', 'inventory_adjustment'], true), 404);
        $result = $engine->post($type, $id, $request->user()->id);
        return back()->with('success', 'Dokumen berhasil diposting. Jurnal #'.($result['journal_id'] ?? '-'));
    }
}
