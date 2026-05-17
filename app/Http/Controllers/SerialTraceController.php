<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ExportVoucher;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SerialTraceController extends Controller
{
    public function index(Request $request)
    {
        return view('serial_trace.index', [
            'product' => null,
            'movements' => collect(),
            'fallbackExportVoucher' => null,
            'historyReady' => $this->stockMovementsReady(),
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
        $with = ['productCatalog', 'supplier', 'location'];
        if (Schema::hasTable('import_vouchers') && Schema::hasColumn('products', 'import_voucher_id')) {
            $with[] = 'importVoucher';
        }
        if (Schema::hasColumn('products', 'export_voucher_id')) {
            $with[] = 'exportVoucher';
        }

        $product = Product::query()
            ->with($with)
            ->where('serial_number', $serial)
            ->first();

        $historyReady = $this->stockMovementsReady();
        $movements = $historyReady
            ? StockMovement::query()
                ->with(['fromLocation', 'toLocation', 'importVoucher', 'exportVoucher', 'user', 'productCatalog'])
                ->where('serial_number', $serial)
                ->orderBy('occurred_at')
                ->get()
            : collect();

        $fallbackExportVoucher = null;
        if ($product && (int) $product->status === 2 && !$product->exportVoucher) {
            $fallbackExportVoucher = $this->findExportVoucherBySerial($serial);
        }

        return view('serial_trace.index', [
            'product' => $product,
            'movements' => $movements,
            'fallbackExportVoucher' => $fallbackExportVoucher,
            'historyReady' => $historyReady,
            'serial' => $serial,
            'canViewCost' => $request->user()?->canViewCostPrices(),
        ]);
    }

    private function stockMovementsReady(): bool
    {
        return Schema::hasTable('stock_movements');
    }

    private function findExportVoucherBySerial(string $serial): ?ExportVoucher
    {
        return ExportVoucher::query()
            ->where('items', 'like', '%' . $serial . '%')
            ->orderByDesc('exported_at')
            ->get()
            ->first(function (ExportVoucher $voucher) use ($serial) {
                $items = is_string($voucher->items) ? json_decode($voucher->items, true) : $voucher->items;

                return collect($items ?: [])->contains(function ($item) use ($serial) {
                    return in_array($serial, $item['serials'] ?? [], true);
                });
            });
    }
}
