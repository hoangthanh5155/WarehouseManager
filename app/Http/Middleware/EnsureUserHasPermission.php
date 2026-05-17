<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();

        $allowed = match ($ability) {
            'financial_reports' => $user?->canViewFinancialReports(),
            'cost_prices' => $user?->canViewCostPrices(),
            'full_product_detail' => $user?->canAccessFullProductDetail(),
            'import_stock' => $user?->canImportStock(),
            'export_stock' => $user?->canExportStock(),
            'edit_export_metadata' => $user?->canEditExportMetadata(),
            'manage_settings' => $user?->canManageSettings(),
            'manage_master_data' => $user?->canManageMasterData(),
            'manage_warehouse_catalogs' => $user?->canManageWarehouseCatalogs(),
            'operations_dashboard' => $user?->canViewOperationsDashboard(),
            default => false,
        };

        if (!$allowed) {
            abort(403, 'Bạn không có quyền truy cập chức năng này.');
        }

        return $next($request);
    }
}
