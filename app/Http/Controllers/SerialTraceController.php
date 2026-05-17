<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class SerialTraceController extends Controller
{
    public function index(Request $request)
    {
        return view('serial_trace.index', [
            'product' => null,
            'movements' => collect(),
            'serial' => $request->query('serial_number', ''),
            'canViewCost' => $request->user()?->canViewCostPrices(),
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => ['required', 'string', 'max:255'],
        ]);

        $serial = trim($validated['serial_number']);
        $product = Product::query()
            ->with(['productCatalog', 'supplier', 'location', 'importVoucher', 'exportVoucher'])
            ->where('serial_number', $serial)
            ->first();

        $movements = StockMovement::query()
            ->with(['fromLocation', 'toLocation', 'importVoucher', 'exportVoucher', 'user', 'productCatalog'])
            ->where('serial_number', $serial)
            ->orderBy('occurred_at')
            ->get();

        return view('serial_trace.index', [
            'product' => $product,
            'movements' => $movements,
            'serial' => $serial,
            'canViewCost' => $request->user()?->canViewCostPrices(),
        ]);
    }
}
