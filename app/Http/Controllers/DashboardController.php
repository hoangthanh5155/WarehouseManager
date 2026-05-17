<?php

namespace App\Http\Controllers;

use App\Models\ExportVoucher;
use App\Models\Product;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        abort_unless($user?->canViewOperationsDashboard(), 403);

        if (!$user->canViewFinancialReports()) {
            $totalInStock = Product::where('status', 1)->count();
            $lowStockThreshold = 3;
            $lowStockProducts = Product::query()
                ->select('product_catalog_id', DB::raw('count(*) as stock_count'))
                ->where('status', 1)
                ->groupBy('product_catalog_id')
                ->having('stock_count', '<=', $lowStockThreshold)
                ->get()
                ->count();

            $recentVouchers = ExportVoucher::orderByDesc('exported_at')->limit(6)->get();
            $recentImports = Product::with(['productCatalog', 'supplier', 'location'])
                ->latest()
                ->limit(6)
                ->get();

            $stockBaseQuery = Product::query()
                ->join('product_catalogs', 'products.product_catalog_id', '=', 'product_catalogs.id')
                ->leftJoin('suppliers', 'product_catalogs.supplier_id', '=', 'suppliers.id')
                ->where('products.status', 1)
                ->groupBy('products.product_catalog_id', 'product_catalogs.product_name', 'suppliers.name')
                ->selectRaw('products.product_catalog_id, product_catalogs.product_name, suppliers.name as supplier_name, COUNT(*) as stock_count');

            $lowStockList = (clone $stockBaseQuery)
                ->having('stock_count', '<=', $lowStockThreshold)
                ->orderBy('stock_count')
                ->orderBy('product_catalogs.product_name')
                ->limit(5)
                ->get();

            $highStockProducts = (clone $stockBaseQuery)
                ->orderByDesc('stock_count')
                ->limit(5)
                ->get();

            return view('dashboard.index', [
                'isOperationalDashboard' => true,
                'totalInStock' => $totalInStock,
                'lowStockProducts' => $lowStockProducts,
                'lowStockThreshold' => $lowStockThreshold,
                'recentVouchers' => $recentVouchers,
                'recentImports' => $recentImports,
                'lowStockList' => $lowStockList,
                'highStockProducts' => $highStockProducts,
            ]);
        }

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

        $sevenDayStart = Carbon::today()->subDays(6)->startOfDay();
        $sevenDayEnd = Carbon::today()->endOfDay();
        $sevenDayRevenueRows = ExportVoucher::query()
            ->selectRaw('DATE(exported_at) as revenue_date, COALESCE(SUM(total_amount), 0) as revenue')
            ->whereBetween('exported_at', [$sevenDayStart, $sevenDayEnd])
            ->groupBy(DB::raw('DATE(exported_at)'))
            ->pluck('revenue', 'revenue_date');

        $sevenDayRevenue = collect(CarbonPeriod::create($sevenDayStart->copy()->startOfDay(), Carbon::today()))
            ->map(function (Carbon $date) use ($sevenDayRevenueRows) {
                $key = $date->toDateString();

                return [
                    'date' => $key,
                    'label' => $date->format('d/m'),
                    'weekday' => $date->locale('vi')->isoFormat('dd'),
                    'revenue' => (float) ($sevenDayRevenueRows[$key] ?? 0),
                ];
            });

        $maxSevenDayRevenue = max((float) $sevenDayRevenue->max('revenue'), 1);
        $sevenDayChartLabels = $sevenDayRevenue->pluck('label')->values();
        $sevenDayChartValues = $sevenDayRevenue->pluck('revenue')->values();
        $hasSevenDayRevenue = $sevenDayRevenue->sum('revenue') > 0;

        $stockBaseQuery = Product::query()
            ->join('product_catalogs', 'products.product_catalog_id', '=', 'product_catalogs.id')
            ->leftJoin('suppliers', 'product_catalogs.supplier_id', '=', 'suppliers.id')
            ->where('products.status', 1)
            ->groupBy(
                'products.product_catalog_id',
                'product_catalogs.product_name',
                'product_catalogs.wholesale_price',
                'suppliers.name'
            )
            ->selectRaw('
                products.product_catalog_id,
                product_catalogs.product_name,
                COALESCE(product_catalogs.wholesale_price, 0) as wholesale_price,
                suppliers.name as supplier_name,
                COUNT(*) as stock_count,
                COUNT(*) * COALESCE(product_catalogs.wholesale_price, 0) as inventory_value
            ');

        $lowStockList = (clone $stockBaseQuery)
            ->having('stock_count', '<=', $lowStockThreshold)
            ->orderBy('stock_count')
            ->orderBy('product_catalogs.product_name')
            ->limit(5)
            ->get();

        $highInventoryValueProducts = (clone $stockBaseQuery)
            ->orderByDesc('inventory_value')
            ->limit(5)
            ->get();

        $highStockProducts = (clone $stockBaseQuery)
            ->orderByDesc('stock_count')
            ->limit(5)
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
            'recentImports',
            'sevenDayRevenue',
            'maxSevenDayRevenue',
            'sevenDayChartLabels',
            'sevenDayChartValues',
            'hasSevenDayRevenue',
            'lowStockList',
            'highInventoryValueProducts',
            'highStockProducts'
        ));
    }
}
