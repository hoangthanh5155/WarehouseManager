<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'active.user'])->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('products.index');
    Route::match(['get', 'post', 'put'], '/products/catalog/{id}', [ProductController::class, 'showCatalog'])
        ->middleware('permission:full_product_detail')
        ->name('products.showCatalog');

    Route::get('/import', [ProductController::class, 'import'])->middleware('permission:import_stock')->name('products.import');
    Route::post('/import/store', [ProductController::class, 'storeManual'])->middleware('permission:import_stock')->name('products.store');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->middleware('permission:financial_reports')->name('reports.revenue');

    Route::resource('product-catalogs', ProductCatalogController::class)->middleware('permission:cost_prices');
    Route::resource('suppliers', SupplierController::class)->middleware('permission:manage_warehouse_catalogs');
    Route::resource('locations', LocationController::class)->middleware('permission:manage_warehouse_catalogs');

    Route::get('/smart-suggestion', [ProductController::class, 'smartSuggestion'])->middleware('permission:import_stock')->name('products.suggestion');

    Route::get('/export', [ExportController::class, 'index'])->middleware('permission:export_stock')->name('export.index');
    Route::patch('/export/vouchers/{voucher}/metadata', [ExportController::class, 'updateMetadata'])->middleware('permission:edit_export_metadata')->name('export.metadata.update');
    Route::get('/export/print/{id}', [ExportController::class, 'print'])->middleware('permission:export_stock')->name('export.print');

    Route::prefix('api/export')->group(function () {
        Route::get('/check-sn/{serial_number}', [ExportController::class, 'checkSerial'])->middleware('permission:export_stock')->name('export.checkSn');
        Route::post('/submit', [ExportController::class, 'store'])->middleware('permission:export_stock')->name('export.submit');
    });

    Route::middleware('can.manage.users')->group(function () {
        Route::resource('users', InternalUserController::class)->except(['show', 'destroy']);
        Route::patch('/users/{user}/status', [InternalUserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::post('/users/{user}/reset-password', [InternalUserController::class, 'resetPassword'])->name('users.resetPassword');
    });

    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/settings/company', [CompanyProfileController::class, 'edit'])->name('settings.company.edit');
        Route::put('/settings/company', [CompanyProfileController::class, 'update'])->name('settings.company.update');
    });
});
