<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ExportController;

// [1] TRANG CHỦ: Trả về danh sách sản phẩm đại diện (Gom nhóm thông minh)
Route::get('/', [ProductController::class, 'index'])->name('products.index');

// [2] CHI TIẾT SẢN PHẨM & SỬA GIÁ: Xem tất cả các mã SN và cập nhật giá sỉ/lẻ
Route::match(['get', 'post', 'put'], '/products/catalog/{id}', [ProductController::class, 'showCatalog'])->name('products.showCatalog');

// [3] VẬN HÀNH: Màn hình Nhập kho 3 Tabs thần thánh
Route::get('/import', [ProductController::class, 'import'])->name('products.import');
Route::post('/import/store', [ProductController::class, 'storeManual'])->name('products.store');

// [4] THỐNG KÊ: Trang Dashboard 4 ô số liệu
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// [5] QUẢN LÝ DANH MỤC
Route::resource('product-catalogs', ProductCatalogController::class);
Route::resource('suppliers', SupplierController::class);
Route::resource('locations', LocationController::class);

// [6] API Lấy gợi ý thông minh (Giá + Vị trí kệ cũ)
Route::get('/smart-suggestion', [ProductController::class, 'smartSuggestion'])->name('products.suggestion');

// =========================================================================
// [7] QUẢN LÝ XUẤT KHO & IN HÓA ĐƠN (Hệ thống 2 Bảng & Đơn Mở Rộng)
// =========================================================================

// Giao diện tạo đơn xuất kho (Đơn chính + Đơn mở rộng)
Route::get('/export', [ExportController::class, 'index'])->name('export.index');

// Giao diện in hóa đơn A4 chuẩn phôi Thái Sơn
Route::get('/export/print/{id}', [ExportController::class, 'print'])->name('export.print');

// =========================================================================
// [8] API PHỤC VỤ XUẤT KHO (Được bọc CSRF Token mượt mà)
// =========================================================================
Route::prefix('api/export')->group(function () {
    // API kiểm tra mã SN trong kho xem có tồn tại và đúng sản phẩm không
    Route::get('/check-sn/{serial_number}', [ExportController::class, 'checkSerial'])->name('export.checkSn');

    // API lưu đơn xuất kho tổng hợp (Đơn chính + Đơn mở rộng)
    Route::post('/submit', [ExportController::class, 'store'])->name('export.submit');
});