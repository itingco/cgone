<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class BusinessUnitSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_unit_id' => ['required', 'integer'],
        ]);

        $businessUnit = BusinessUnit::query()
            ->whereKey((int) $data['business_unit_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $request->session()->put(
            (string) config('erp_context.business_unit_session_key', 'erp_business_unit_id'),
            (int) $businessUnit->id
        );

        return back()->with('success', 'Business Unit aktif: '.$businessUnit->code.' - '.$businessUnit->name.'.');
    }
}
