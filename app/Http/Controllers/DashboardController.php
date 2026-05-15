<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Location;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Tổng số máy đang có trong kho (status = 1)
        $totalInStock = Product::where('status', 1)->count();

        // 2. Số lượng máy nhập mới hôm nay
        $importedToday = Product::whereDate('created_at', Carbon::today())->count();

        // 3. Số lượng máy đã xuất hôm nay
        $exportedToday = Product::where('status', 0) // Giả định status = 0 là đã xuất
            ->whereDate('updated_at', Carbon::today())
            ->count();

        // 4. Tổng số vị trí kệ
        $totalLocations = Location::count();

        return view('dashboard.index', compact(
            'totalInStock', 
            'importedToday', 
            'exportedToday', 
            'totalLocations'
        ));
    }
}