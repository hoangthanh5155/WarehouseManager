<?php

namespace App\Http\Controllers;

use App\Models\ExportVoucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
