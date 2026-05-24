<?php

namespace App\Http\Controllers;

use App\Services\Warehouse\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardService $dashboardService)
    {
        $user = $request->user();
        abort_unless($user?->canViewOperationsDashboard(), 403);

        return view('dashboard.index', $dashboardService->dashboardFor($user));
    }
}
