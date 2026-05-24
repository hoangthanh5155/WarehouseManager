<?php

namespace App\Http\Controllers;

use App\Services\Warehouse\SerialTraceService;
use Illuminate\Http\Request;

class SerialTraceController extends Controller
{
    public function index(Request $request)
    {
        return view('serial_trace.index', [
            'serial' => $request->query('serial_number', ''),
            ...$this->emptyTracePayload($request),
        ]);
    }

    public function search(Request $request, SerialTraceService $serialTraceService)
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:255'],
        ]);

        $serial = trim($validated['serial_number']);
        $trace = $serialTraceService->trace($serial, (bool) $request->user()?->canViewCostPrices());

        return view('serial_trace.index', [
            'serial' => $serial,
            ...$trace,
        ]);
    }

    private function emptyTracePayload(Request $request): array
    {
        return [
            'product' => null,
            'movements' => collect(),
            'importVoucher' => null,
            'importVoucherItem' => null,
            'exportVoucher' => null,
            'exportVoucherItem' => null,
            'statusText' => null,
            'canViewCost' => (bool) $request->user()?->canViewCostPrices(),
        ];
    }
}
