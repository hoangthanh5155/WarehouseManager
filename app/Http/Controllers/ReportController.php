<?php

namespace App\Http\Controllers;

use App\Models\ImportVoucher;
use App\Services\Warehouse\WarehouseReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function revenue(Request $request, WarehouseReportService $reportService)
    {
        [$period, $startDate, $endDate] = $this->resolveDateRange($request);
        $canViewCost = (bool) $request->user()?->canViewCostPrices();

        return view('reports.revenue', [
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'canViewCost' => $canViewCost,
            ...$reportService->revenueReport($startDate, $endDate, $request),
        ]);
    }

    public function warehouseHistory(Request $request, WarehouseReportService $reportService)
    {
        [$startDate, $endDate] = $this->resolveExplicitDateRange($request);
        $canViewCost = (bool) $request->user()?->canViewCostPrices();

        return view('reports.warehouse_history', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'canViewCost' => $canViewCost,
            ...$reportService->warehouseHistoryReport($startDate, $endDate, $request),
        ]);
    }

    public function inventorySummary(Request $request, WarehouseReportService $reportService)
    {
        [$startDate, $endDate] = $this->resolveExplicitDateRange($request);
        $canViewCost = (bool) $request->user()?->canViewCostPrices();

        return view('reports.inventory_summary', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'canViewCost' => $canViewCost,
            ...$reportService->inventorySummaryReport($startDate, $endDate, $request),
        ]);
    }

    public function importVoucherDetail(
        ImportVoucher $importVoucher,
        Request $request,
        WarehouseReportService $reportService
    ) {
        return view('reports.import_voucher', [
            'importVoucher' => $reportService->importVoucherDetail($importVoucher),
            'canViewCost' => (bool) $request->user()?->canViewCostPrices(),
        ]);
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

    private function resolveExplicitDateRange(Request $request): array
    {
        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->startOfMonth();

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
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
