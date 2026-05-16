<?php

namespace App\Http\Controllers;

use App\Models\ExportVoucher;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $monthlyRevenue = (float) ExportVoucher::whereBetween('exported_at', [$monthStart, $monthEnd])
            ->sum('total_amount');

        $totalInStock = Product::where('status', 1)->count();

        $inventoryValue = (float) Product::query()
            ->join('product_catalogs', 'products.product_catalog_id', '=', 'product_catalogs.id')
            ->where('products.status', 1)
            ->sum('product_catalogs.wholesale_price');

        $lowStockThreshold = 3;
        $lowStockProducts = Product::query()
            ->select('product_catalog_id', DB::raw('count(*) as stock_count'))
            ->where('status', 1)
            ->groupBy('product_catalog_id')
            ->having('stock_count', '<=', $lowStockThreshold)
            ->get()
            ->count();

        $todayRevenue = (float) ExportVoucher::whereDate('exported_at', Carbon::today())->sum('total_amount');
        $monthlyGrossProfit = (float) ExportVoucher::whereBetween('exported_at', [$monthStart, $monthEnd])
            ->selectRaw('COALESCE(SUM(total_amount - total_cost), 0) as gross_profit')
            ->value('gross_profit');
        $monthlyOrders = ExportVoucher::whereBetween('exported_at', [$monthStart, $monthEnd])->count();

        $recentVouchers = ExportVoucher::orderByDesc('exported_at')->limit(6)->get();
        $recentImports = Product::with(['productCatalog', 'supplier', 'location'])
            ->latest()
            ->limit(6)
            ->get();

        return view('dashboard.index', compact(
            'monthlyRevenue',
            'totalInStock',
            'inventoryValue',
            'lowStockProducts',
            'lowStockThreshold',
            'todayRevenue',
            'monthlyGrossProfit',
            'monthlyOrders',
            'recentVouchers',
            'recentImports'
        ));
    }
}
