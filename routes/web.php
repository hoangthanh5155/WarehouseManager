<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryBatchController;
use App\Http\Controllers\DeliveryBatchPageController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SerialTraceController;
use App\Http\Controllers\SupplierController;

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active.user', 'password.changed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/password', [ProfileController::class, 'password'])->name('profile.password');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::match(['get', 'post', 'put'], '/products/catalog/{id}', [ProductController::class, 'showCatalog'])
        ->middleware('permission:full_product_detail')
        ->name('products.showCatalog');

    Route::get('/import', [ProductController::class, 'import'])->middleware('permission:import_stock')->name('products.import');
    Route::post('/import/store', [ProductController::class, 'storeManual'])->middleware('permission:import_stock')->name('products.store');

    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:operations_dashboard')->name('dashboard');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->middleware('permission:financial_reports')->name('reports.revenue');
    Route::get('/reports/inventory-summary', [ReportController::class, 'inventorySummary'])->middleware('permission:warehouse_reports')->name('reports.inventory_summary');
    Route::get('/reports/warehouse-history', [ReportController::class, 'warehouseHistory'])->middleware('permission:warehouse_history')->name('reports.warehouse_history');
    Route::get('/reports/warehouse-history/imports/{importVoucher}', [ReportController::class, 'importVoucherDetail'])->middleware('permission:warehouse_history')->name('reports.warehouse_history.imports.show');

    Route::get('/serial-trace', [SerialTraceController::class, 'index'])->middleware('permission:trace_serial')->name('serial.trace.index');
    Route::get('/serial-trace/search', [SerialTraceController::class, 'search'])->middleware('permission:trace_serial')->name('serial.trace.search');

    Route::resource('product-catalogs', ProductCatalogController::class)->middleware('permission:manage_master_data');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:manage_master_data');
    Route::resource('locations', LocationController::class)->middleware('permission:manage_master_data');

    Route::get('/smart-suggestion', [ProductController::class, 'smartSuggestion'])->middleware('permission:import_stock')->name('products.suggestion');

    Route::get('/export', [ExportController::class, 'index'])->middleware('permission:export_stock')->name('export.index');
    Route::patch('/export/vouchers/{voucher}/metadata', [ExportController::class, 'updateMetadata'])->middleware('permission:edit_export_metadata')->name('export.metadata.update');
    Route::get('/export/print/{id}', [ExportController::class, 'print'])->middleware('permission:export_stock')->name('export.print');

    Route::middleware('permission:export_stock')->group(function () {
        Route::get('/delivery/orders', [DeliveryBatchPageController::class, 'ordersIndex'])->name('delivery.orders.index');
        Route::get('/delivery/orders/create', [DeliveryBatchPageController::class, 'ordersCreate'])->name('delivery.orders.create');
        Route::get('/delivery/batches', [DeliveryBatchPageController::class, 'batchesIndex'])->name('delivery.batches.index');
        Route::get('/delivery/batches/{deliveryBatch}', [DeliveryBatchPageController::class, 'batchesShow'])->name('delivery.batches.show');
    });

    Route::prefix('api/export')->group(function () {
        Route::get('/check-sn/{serial_number}', [ExportController::class, 'checkSerial'])->middleware('permission:export_stock')->name('export.checkSn');
        Route::post('/submit', [ExportController::class, 'store'])->middleware('permission:export_stock')->name('export.submit');
    });

    Route::prefix('api/delivery-batches')->middleware('permission:export_stock')->group(function () {
        Route::post('/orders', [DeliveryBatchController::class, 'storeOrder'])->name('delivery.orders.store');
        Route::post('/', [DeliveryBatchController::class, 'storeBatch'])->name('delivery.batches.store');
        Route::post('/{deliveryBatch}/orders', [DeliveryBatchController::class, 'addOrder'])->name('delivery.batches.orders.store');
        Route::post('/{deliveryBatch}/serials/reserve', [DeliveryBatchController::class, 'reserveSerials'])->name('delivery.batches.serials.reserve');
        Route::post('/orders/{deliveryBatchOrder}/serials/assign', [DeliveryBatchController::class, 'assignOrderSerials'])->name('delivery.orders.serials.assign');
        Route::post('/orders/{deliveryBatchOrder}/deliver', [DeliveryBatchController::class, 'deliverOrder'])->name('delivery.orders.deliver');
        Route::post('/orders/{deliveryBatchOrder}/fail', [DeliveryBatchController::class, 'failOrder'])->name('delivery.orders.fail');
    });

    Route::middleware('can.manage.users')->group(function () {
        Route::resource('users', InternalUserController::class)->except(['show', 'destroy']);
        Route::patch('/users/{user}/status', [InternalUserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::post('/users/{user}/reset-link', [InternalUserController::class, 'generateResetLink'])->name('users.resetLink');
    });

    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/settings/company', [CompanyProfileController::class, 'edit'])->name('settings.company.edit');
        Route::put('/settings/company', [CompanyProfileController::class, 'update'])->name('settings.company.update');
    });
});
