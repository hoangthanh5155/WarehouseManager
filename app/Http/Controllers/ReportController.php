<?php

namespace App\Http\Controllers;

use App\Models\ExportVoucher;
use App\Models\ImportVoucher;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    public function revenue(Request $request)
    {
        [$period, $startDate, $endDate] = $this->resolveDateRange($request);

        $voucherBaseQuery = ExportVoucher::whereBetween('exported_at', [$startDate, $endDate]);

        $summary = (clone $voucherBaseQuery)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as export_order_count')
            ->first();

        $totalRevenue = (float) $summary->total_revenue;
        $totalCost = (float) $summary->total_cost;
        $grossProfit = $totalRevenue - $totalCost;
        $exportOrderCount = (int) $summary->export_order_count;

        $estimatedImportValue = (float) DB::table('products')
            ->join('product_catalogs', 'products.product_catalog_id', '=', 'product_catalogs.id')
            ->whereBetween('products.created_at', [$startDate, $endDate])
            ->sum('product_catalogs.wholesale_price');

        $vouchers = (clone $voucherBaseQuery)
            ->orderByDesc('exported_at')
            ->paginate(20)
            ->withQueryString();

        return view('reports.revenue', compact(
            'period',
            'startDate',
            'endDate',
            'totalRevenue',
            'totalCost',
            'grossProfit',
            'exportOrderCount',
            'estimatedImportValue',
            'vouchers'
        ));
    }

    public function warehouseHistory(Request $request)
    {
        $user = $request->user();
        $canViewCost = $user?->canViewCostPrices();
        $startDate = $request->filled('start_date') ? Carbon::parse($request->query('start_date'))->startOfDay() : now()->subDays(6)->startOfDay();
        $endDate = $request->filled('end_date') ? Carbon::parse($request->query('end_date'))->endOfDay() : now()->endOfDay();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        if (!$this->warehouseHistorySchemaReady()) {
            return view('reports.warehouse_history_uninitialized', compact('startDate', 'endDate'));
        }

        $baseQuery = StockMovement::query()
            ->with(['product.productCatalog', 'product.location', 'product.exportVoucher', 'productCatalog', 'supplier', 'fromLocation', 'toLocation', 'importVoucher', 'exportVoucher', 'user'])
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->when($request->filled('movement_type') && in_array($request->query('movement_type'), ['import', 'export'], true), fn ($query) => $query->where('movement_type', $request->query('movement_type')))
            ->when($request->filled('serial_number'), fn ($query) => $query->where('serial_number', 'like', '%' . $request->query('serial_number') . '%'))
            ->when($request->filled('product_name'), function ($query) use ($request) {
                $query->whereHas('productCatalog', fn ($productQuery) => $productQuery->where('product_name', 'like', '%' . $request->query('product_name') . '%'));
            })
            ->when($request->filled('user_id'), fn ($query) => $query->where('user_id', $request->query('user_id')))
            ->when($request->filled('voucher_code'), function ($query) use ($request) {
                $voucherCode = $request->query('voucher_code');
                $query->where(function ($subQuery) use ($voucherCode) {
                    $subQuery
                        ->whereHas('importVoucher', fn ($voucherQuery) => $voucherQuery->where('import_code', 'like', '%' . $voucherCode . '%'))
                        ->orWhereHas('exportVoucher', fn ($voucherQuery) => $voucherQuery->where('export_code', 'like', '%' . $voucherCode . '%'));
                });
            });

        $summary = (clone $baseQuery)
            ->selectRaw('COUNT(*) as total_movements')
            ->selectRaw("SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END) as imported_qty")
            ->selectRaw("SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END) as exported_qty")
            ->selectRaw('COUNT(DISTINCT product_catalog_id) as product_count')
            ->first();

        $dailyGroups = (clone $baseQuery)
            ->selectRaw('DATE(occurred_at) as movement_date')
            ->selectRaw('COUNT(DISTINCT import_voucher_id) as import_batches')
            ->selectRaw("SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END) as imported_qty")
            ->selectRaw("SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END) as exported_qty")
            ->selectRaw('COUNT(DISTINCT product_catalog_id) as product_count')
            ->groupByRaw('DATE(occurred_at)')
            ->orderByDesc('movement_date')
            ->get();

        $movements = (clone $baseQuery)
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        $productGroups = $movements->getCollection()
            ->groupBy(fn ($movement) => $movement->productCatalog?->product_name ?: 'Sản phẩm không xác định');
        $users = User::query()->orderBy('display_name')->orderBy('name')->get(['id', 'name', 'display_name']);

        return view('reports.warehouse_history', compact(
            'startDate',
            'endDate',
            'summary',
            'dailyGroups',
            'movements',
            'productGroups',
            'canViewCost',
            'users'
        ));
    }

    public function inventorySummary()
    {
        return view('reports.inventory_summary');
    }

    public function importVoucherDetail(ImportVoucher $importVoucher)
    {
        $importVoucher->load(['supplier', 'productCatalog', 'location', 'user', 'products.exportVoucher', 'products.location']);

        return view('reports.import_voucher', compact('importVoucher'));
    }

    private function warehouseHistorySchemaReady(): bool
    {
        return Schema::hasTable('stock_movements')
            && Schema::hasTable('import_vouchers')
            && Schema::hasColumn('products', 'import_voucher_id')
            && Schema::hasColumn('products', 'imported_at')
            && Schema::hasColumn('products', 'export_voucher_id')
            && Schema::hasColumn('products', 'exported_at');
    }

    private function resolveDateRange(Request $request): array
    {
        $period = $request->query('period', $request->query('range', 'month'));
        $today = Carbon::today();

        return match ($period) {
            'today' => [
                'today',
                $today->copy()->startOfDay(),
                $today->copy()->endOfDay(),
            ],
            '7days', 'last7' => [
                '7days',
                $today->copy()->subDays(6)->startOfDay(),
                $today->copy()->endOfDay(),
            ],
            'year', 'this_year' => [
                'year',
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
            ],
            'custom' => $this->resolveCustomRange($request, $today),
            default => [
                'month',
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
            ],
        };
    }

    private function resolveCustomRange(Request $request, Carbon $fallbackDate): array
    {
        try {
            $startDate = $request->filled('start_date')
                ? Carbon::parse($request->query('start_date'))->startOfDay()
                : $fallbackDate->copy()->startOfMonth();

            $endDate = $request->filled('end_date')
                ? Carbon::parse($request->query('end_date'))->endOfDay()
                : $fallbackDate->copy()->endOfMonth();
        } catch (\Exception) {
            $startDate = $fallbackDate->copy()->startOfMonth();
            $endDate = $fallbackDate->copy()->endOfMonth();
        }

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return ['custom', $startDate, $endDate];
    }
}
