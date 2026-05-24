<?php

namespace App\Services\Warehouse;

use App\Models\ExportVoucher;
use App\Models\ImportVoucher;
use App\Models\User;
use App\Support\Warehouse\WarehouseConstants;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function dashboardFor(User $user, int $lowStockThreshold = 3): array
    {
        $canViewCost = $user->canViewCostPrices();
        $operationalData = $this->operationalData($lowStockThreshold, $canViewCost);

        if (!$user->canViewFinancialReports()) {
            return [
                'isOperationalDashboard' => true,
                'canViewCost' => $canViewCost,
                ...$operationalData,
            ];
        }

        return [
            'isOperationalDashboard' => false,
            'canViewCost' => $canViewCost,
            ...$this->financialData(),
            ...$operationalData,
        ];
    }

    public function operationalData(int $lowStockThreshold = 3, bool $includeCost = false): array
    {
        return [
            'totalInStock' => $this->totalInStock(),
            'inventoryValue' => $includeCost ? $this->currentInventoryValue() : null,
            'lowStockProducts' => $this->lowStockCatalogCount($lowStockThreshold),
            'lowStockThreshold' => $lowStockThreshold,
            'recentVouchers' => $this->recentExportVouchers(),
            'recentImports' => $this->recentImportVouchers(),
            'lowStockList' => $this->lowStockCatalogs($lowStockThreshold, $includeCost),
            'highStockProducts' => $this->topStockCatalogs($includeCost),
            'highInventoryValueProducts' => $includeCost ? $this->topInventoryValueCatalogs() : collect(),
        ];
    }

    public function financialData(): array
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        $todayStart = Carbon::today()->startOfDay();
        $todayEnd = Carbon::today()->endOfDay();
        $monthlySummary = $this->exportItemSummary($monthStart, $monthEnd);
        $todaySummary = $this->exportItemSummary($todayStart, $todayEnd);
        $sevenDayRevenue = $this->sevenDayRevenue();

        return [
            'monthlyRevenue' => $monthlySummary['total_amount'],
            'todayRevenue' => $todaySummary['total_amount'],
            'monthlyGrossProfit' => $monthlySummary['total_amount'] - $monthlySummary['total_cost'],
            'monthlyOrders' => ExportVoucher::query()
                ->whereBetween('exported_at', [$monthStart, $monthEnd])
                ->count(),
            ...$sevenDayRevenue,
        ];
    }

    public function totalInStock(): int
    {
        return DB::table('products')
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->count();
    }

    public function currentInventoryValue(): float
    {
        return (float) DB::table('products')
            ->join('product_catalogs', 'products.product_catalog_id', '=', 'product_catalogs.id')
            ->where('products.status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->sum(DB::raw('COALESCE(product_catalogs.wholesale_price, 0)'));
    }

    public function lowStockCatalogCount(int $threshold): int
    {
        return DB::query()
            ->fromSub($this->stockByCatalogQuery(), 'stock_by_catalog')
            ->where('stock_count', '<=', $threshold)
            ->count();
    }

    public function lowStockCatalogs(int $threshold, bool $includeCost = false)
    {
        return $this->stockByCatalogQuery($includeCost)
            ->having('stock_count', '<=', $threshold)
            ->orderBy('stock_count')
            ->orderBy('product_name')
            ->limit(5)
            ->get();
    }

    public function topStockCatalogs(bool $includeCost = false)
    {
        return $this->stockByCatalogQuery($includeCost)
            ->orderByDesc('stock_count')
            ->orderBy('product_name')
            ->limit(5)
            ->get();
    }

    public function topInventoryValueCatalogs()
    {
        return $this->stockByCatalogQuery(true)
            ->orderByDesc('inventory_value')
            ->orderBy('product_name')
            ->limit(5)
            ->get();
    }

    public function recentExportVouchers(int $limit = 6)
    {
        return ExportVoucher::query()
            ->withSum('items as item_total_amount', 'total_amount')
            ->orderByDesc('exported_at')
            ->limit($limit)
            ->get();
    }

    public function recentImportVouchers(int $limit = 6)
    {
        return ImportVoucher::query()
            ->with(['supplier', 'productCatalog', 'location'])
            ->orderByDesc('imported_at')
            ->limit($limit)
            ->get();
    }

    public function sevenDayRevenue(): array
    {
        $start = Carbon::today()->subDays(6)->startOfDay();
        $end = Carbon::today()->endOfDay();
        $rows = DB::table('export_voucher_items')
            ->join('export_vouchers', 'export_vouchers.id', '=', 'export_voucher_items.export_voucher_id')
            ->selectRaw('DATE(export_vouchers.exported_at) as revenue_date')
            ->selectRaw('COALESCE(SUM(export_voucher_items.total_amount), 0) as revenue')
            ->whereBetween('export_vouchers.exported_at', [$start, $end])
            ->groupByRaw('DATE(export_vouchers.exported_at)')
            ->pluck('revenue', 'revenue_date');

        $sevenDayRevenue = collect(CarbonPeriod::create($start->copy()->startOfDay(), Carbon::today()))
            ->map(function (Carbon $date) use ($rows) {
                $key = $date->toDateString();

                return [
                    'date' => $key,
                    'label' => $date->format('d/m'),
                    'weekday' => $date->locale('vi')->isoFormat('dd'),
                    'revenue' => (float) ($rows[$key] ?? 0),
                ];
            });

        return [
            'sevenDayRevenue' => $sevenDayRevenue,
            'maxSevenDayRevenue' => max((float) $sevenDayRevenue->max('revenue'), 1),
            'sevenDayChartLabels' => $sevenDayRevenue->pluck('label')->values(),
            'sevenDayChartValues' => $sevenDayRevenue->pluck('revenue')->values(),
            'hasSevenDayRevenue' => $sevenDayRevenue->sum('revenue') > 0,
        ];
    }

    private function exportItemSummary($startDate, $endDate): array
    {
        $summary = DB::table('export_voucher_items')
            ->join('export_vouchers', 'export_vouchers.id', '=', 'export_voucher_items.export_voucher_id')
            ->whereBetween('export_vouchers.exported_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(export_voucher_items.total_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(export_voucher_items.total_cost), 0) as total_cost')
            ->first();

        return [
            'total_amount' => (float) ($summary->total_amount ?? 0),
            'total_cost' => (float) ($summary->total_cost ?? 0),
        ];
    }

    private function stockByCatalogQuery(bool $includeCost = false)
    {
        $query = DB::table('product_catalogs')
            ->leftJoin('suppliers', 'product_catalogs.supplier_id', '=', 'suppliers.id')
            ->leftJoin('products', function ($join) {
                $join->on('products.product_catalog_id', '=', 'product_catalogs.id')
                    ->where('products.status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK);
            })
            ->select([
                'product_catalogs.id as product_catalog_id',
                'product_catalogs.product_name',
                'suppliers.name as supplier_name',
            ])
            ->selectRaw('COUNT(products.id) as stock_count')
            ->groupBy('product_catalogs.id', 'product_catalogs.product_name', 'suppliers.name');

        if ($includeCost) {
            $query
                ->addSelect('product_catalogs.wholesale_price')
                ->selectRaw('COUNT(products.id) * COALESCE(product_catalogs.wholesale_price, 0) as inventory_value')
                ->groupBy('product_catalogs.wholesale_price');
        }

        return $query;
    }
}
