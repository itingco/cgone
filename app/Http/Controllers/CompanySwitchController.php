<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CompanySwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['company_id' => ['required', 'integer']]);
        abort_unless($request->user()->companies()->whereKey($data['company_id'])->exists(), 403);
        $request->session()->put('company_id', (int) $data['company_id']);
        return back()->with('success', 'Perusahaan aktif berhasil diganti.');
    }
}
