<?php

namespace App\Services\Warehouse;

use App\Models\ExportVoucher;
use App\Models\ImportVoucher;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseReportService
{
    public function revenueReport($startDate, $endDate, Request $request): array
    {
        $itemBaseQuery = DB::table('export_voucher_items')
            ->join('export_vouchers', 'export_vouchers.id', '=', 'export_voucher_items.export_voucher_id')
            ->whereBetween('export_vouchers.exported_at', [$startDate, $endDate]);

        $summary = (clone $itemBaseQuery)
            ->selectRaw('COALESCE(SUM(export_voucher_items.total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(export_voucher_items.total_cost), 0) as total_cost')
            ->selectRaw('COALESCE(SUM(export_voucher_items.quantity), 0) as exported_qty')
            ->first();

        $exportOrderCount = ExportVoucher::query()
            ->whereBetween('exported_at', [$startDate, $endDate])
            ->count();

        $exportedSerialCount = DB::table('export_voucher_item_serials')
            ->join('export_voucher_items', 'export_voucher_items.id', '=', 'export_voucher_item_serials.export_voucher_item_id')
            ->join('export_vouchers', 'export_vouchers.id', '=', 'export_voucher_items.export_voucher_id')
            ->whereBetween('export_vouchers.exported_at', [$startDate, $endDate])
            ->count();

        $vouchers = ExportVoucher::query()
            ->withSum('items as item_total_amount', 'total_amount')
            ->withSum('items as item_total_cost', 'total_cost')
            ->withSum('items as item_quantity', 'quantity')
            ->whereBetween('exported_at', [$startDate, $endDate])
            ->orderByDesc('exported_at')
            ->paginate(20)
            ->withQueryString();

        $totalRevenue = (float) ($summary->total_revenue ?? 0);
        $totalCost = (float) ($summary->total_cost ?? 0);

        return [
            'totalRevenue' => $totalRevenue,
            'totalCost' => $totalCost,
            'grossProfit' => $totalRevenue - $totalCost,
            'exportOrderCount' => $exportOrderCount,
            'exportedProductCount' => (int) ($summary->exported_qty ?? 0),
            'exportedSerialCount' => $exportedSerialCount,
            'vouchers' => $vouchers,
        ];
    }

    public function inventorySummaryReport($startDate, $endDate, Request $request): array
    {
        $openingSubquery = StockMovement::query()
            ->select('product_catalog_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'import' THEN quantity WHEN movement_type = 'export' THEN -quantity ELSE 0 END), 0) as opening_qty")
            ->where('occurred_at', '<', $startDate)
            ->groupBy('product_catalog_id');

        $periodSubquery = StockMovement::query()
            ->select('product_catalog_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END), 0) as imported_qty")
            ->selectRaw("COALESCE(SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END), 0) as exported_qty")
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->groupBy('product_catalog_id');

        $currentStockSubquery = $this->currentStockByCatalog();

        $summaryQuery = DB::table('product_catalogs')
            ->leftJoin('suppliers', 'product_catalogs.supplier_id', '=', 'suppliers.id')
            ->leftJoinSub($openingSubquery, 'opening', 'opening.product_catalog_id', '=', 'product_catalogs.id')
            ->leftJoinSub($periodSubquery, 'period_movements', 'period_movements.product_catalog_id', '=', 'product_catalogs.id')
            ->leftJoinSub($currentStockSubquery, 'current_stock', 'current_stock.product_catalog_id', '=', 'product_catalogs.id')
            ->select([
                'product_catalogs.id',
                'product_catalogs.product_name',
                'product_catalogs.wholesale_price',
                'suppliers.name as supplier_name',
            ])
            ->selectRaw('COALESCE(opening.opening_qty, 0) as opening_qty')
            ->selectRaw('COALESCE(period_movements.imported_qty, 0) as imported_qty')
            ->selectRaw('COALESCE(period_movements.exported_qty, 0) as exported_qty')
            ->selectRaw('(COALESCE(opening.opening_qty, 0) + COALESCE(period_movements.imported_qty, 0) - COALESCE(period_movements.exported_qty, 0)) as closing_qty')
            ->selectRaw('COALESCE(current_stock.current_stock_qty, 0) as current_stock_qty')
            ->selectRaw('((COALESCE(opening.opening_qty, 0) + COALESCE(period_movements.imported_qty, 0) - COALESCE(period_movements.exported_qty, 0)) - COALESCE(current_stock.current_stock_qty, 0)) as variance_qty')
            ->when($request->filled('product_name'), fn ($query) => $query->where('product_catalogs.product_name', 'like', '%' . $request->query('product_name') . '%'))
            ->when($request->filled('supplier_id'), fn ($query) => $query->where('product_catalogs.supplier_id', $request->query('supplier_id')))
            ->where(function ($query) {
                $query
                    ->whereNotNull('opening.product_catalog_id')
                    ->orWhereNotNull('period_movements.product_catalog_id')
                    ->orWhereNotNull('current_stock.product_catalog_id');
            });

        $summaryRows = (clone $summaryQuery)->get();

        $rows = $summaryQuery
            ->orderBy('product_catalogs.product_name')
            ->paginate(50)
            ->withQueryString();

        return [
            'rows' => $rows,
            'totals' => [
                'product_count' => $rows->total(),
                'opening_qty' => $summaryRows->sum('opening_qty'),
                'imported_qty' => $summaryRows->sum('imported_qty'),
                'exported_qty' => $summaryRows->sum('exported_qty'),
                'closing_qty' => $summaryRows->sum('closing_qty'),
                'current_stock_qty' => $summaryRows->sum('current_stock_qty'),
                'variance_qty' => $summaryRows->sum('variance_qty'),
                'current_stock_value' => $summaryRows->sum(fn ($row) => (float) $row->current_stock_qty * (float) $row->wholesale_price),
            ],
            'suppliers' => DB::table('suppliers')->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function warehouseHistoryReport($startDate, $endDate, Request $request): array
    {
        $filterQuery = $this->filteredMovementQuery($startDate, $endDate, $request);

        $summary = (clone $filterQuery)
            ->selectRaw('COUNT(*) as total_movements')
            ->selectRaw("SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END) as imported_qty")
            ->selectRaw("SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END) as exported_qty")
            ->selectRaw('COUNT(DISTINCT product_catalog_id) as product_count')
            ->first();

        $dailyGroups = (clone $filterQuery)
            ->selectRaw('DATE(occurred_at) as movement_date')
            ->selectRaw("SUM(CASE WHEN movement_type = 'import' THEN 1 ELSE 0 END) as import_count")
            ->selectRaw("SUM(CASE WHEN movement_type = 'export' THEN 1 ELSE 0 END) as export_count")
            ->selectRaw("SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END) as imported_qty")
            ->selectRaw("SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END) as exported_qty")
            ->groupByRaw('DATE(occurred_at)')
            ->orderByDesc('movement_date')
            ->get();

        $movements = (clone $filterQuery)
            ->with(['product', 'productCatalog', 'supplier', 'fromLocation', 'toLocation', 'importVoucher', 'exportVoucher', 'user'])
            ->orderByDesc('occurred_at')
            ->paginate(50)
            ->withQueryString();

        return [
            'summary' => $summary,
            'dailyGroups' => $dailyGroups,
            'movements' => $movements,
            'productGroups' => $movements->getCollection()
                ->groupBy(fn ($movement) => $movement->productCatalog?->product_name ?: 'San pham khong xac dinh'),
            'users' => User::query()->orderBy('display_name')->orderBy('name')->get(['id', 'name', 'display_name']),
        ];
    }

    public function importVoucherDetail(ImportVoucher $importVoucher): ImportVoucher
    {
        return $importVoucher->load([
            'supplier',
            'productCatalog',
            'location',
            'user',
            'items.productCatalog',
            'items.location',
            'items.products.exportVoucher',
            'items.products.location',
            'products.exportVoucher',
            'products.location',
            'movements.product',
            'movements.productCatalog',
            'movements.toLocation',
            'movements.user',
        ]);
    }

    public function currentStockByCatalog()
    {
        return DB::table('products')
            ->select('product_catalog_id')
            ->selectRaw('COUNT(*) as current_stock_qty')
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->groupBy('product_catalog_id');
    }

    private function filteredMovementQuery($startDate, $endDate, Request $request)
    {
        return StockMovement::query()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->when($request->filled('movement_type') && in_array($request->query('movement_type'), [
                WarehouseConstants::MOVEMENT_IMPORT,
                WarehouseConstants::MOVEMENT_EXPORT,
            ], true), fn ($query) => $query->where('movement_type', $request->query('movement_type')))
            ->when($request->filled('serial_number'), fn ($query) => $query->where('serial_number', 'like', '%' . $request->query('serial_number') . '%'))
            ->when($request->filled('product_catalog_id'), fn ($query) => $query->where('product_catalog_id', $request->query('product_catalog_id')))
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
    }
}
