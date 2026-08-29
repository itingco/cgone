<?php

namespace App\Http\Controllers;

use App\Application\Integration\SalesPostingService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SalesPostingController extends Controller
{
    public function store(Request $request, SalesPostingService $service): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date', 'before_or_equal:today']]);
        try {
            $count = $service->postDate(
                (int) $request->session()->get('company_id'),
                $data['date'],
                $request->user()->id,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['posting' => $exception->getMessage()]);
        }

        return back()->with('success', "{$count} dokumen penjualan berhasil diposting ke stok dan jurnal ringkasan.");
    }
}
