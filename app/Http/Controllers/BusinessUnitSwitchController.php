<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Deprecated in H1. Business Unit is record data and no longer an application context.
 */
final class BusinessUnitSwitchController extends Controller
{
    public function __invoke(Request $request)
    {
        abort(410, 'Global Business Unit context has been removed. Select Business Unit on each transaction.');
    }
}
