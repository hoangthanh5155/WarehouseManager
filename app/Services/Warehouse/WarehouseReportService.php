<?php

namespace App\Services\Warehouse;

use App\Models\ExportVoucher;
use App\Models\StockMovement;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Facades\DB;

class WarehouseReportService
{
    public function revenueSummary($startDate, $endDate): object
    {
        return ExportVoucher::query()
            ->whereBetween('exported_at', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue')
            ->selectRaw('COALESCE(SUM(total_cost), 0) as total_cost')
            ->selectRaw('COUNT(*) as export_order_count')
            ->first();
    }

    public function movementSummary($startDate, $endDate): object
    {
        return StockMovement::query()
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_movements')
            ->selectRaw("SUM(CASE WHEN movement_type = 'import' THEN quantity ELSE 0 END) as imported_qty")
            ->selectRaw("SUM(CASE WHEN movement_type = 'export' THEN quantity ELSE 0 END) as exported_qty")
            ->selectRaw('COUNT(DISTINCT product_catalog_id) as product_count')
            ->first();
    }

    public function currentStockByCatalog()
    {
        return DB::table('products')
            ->select('product_catalog_id')
            ->selectRaw('COUNT(*) as current_stock_qty')
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->groupBy('product_catalog_id');
    }
}
