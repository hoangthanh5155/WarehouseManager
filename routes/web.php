<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CustomerPortalUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryBatchController;
use App\Http\Controllers\DeliveryBatchPageController;
use App\Http\Controllers\DeliveryVehicleController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InternalUserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesOrderApprovalController;
use App\Http\Controllers\SerialTraceController;
use App\Http\Controllers\ShopAccountController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\StorePortalController;
use App\Http\Controllers\SupplierController;

Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/delivery/public/{token}', [DeliveryBatchPageController::class, 'publicSlip'])->name('delivery.orders.public');
Route::get('/shop/products/{productCatalog}', [ShopController::class, 'show'])->name('shop.products.show');
Route::get('/shop/cart', [ShopController::class, 'cart'])->name('shop.cart');
Route::post('/shop/cart/add', [ShopController::class, 'addToCart'])->name('shop.cart.add');
Route::post('/shop/cart/update', [ShopController::class, 'updateCart'])->name('shop.cart.update');
Route::post('/shop/cart/remove', [ShopController::class, 'removeFromCart'])->name('shop.cart.remove');
Route::get('/shop/checkout', [ShopController::class, 'checkout'])->name('shop.checkout');
Route::post('/shop/checkout', [ShopController::class, 'storeCheckout'])->name('shop.checkout.store');
Route::get('/shop/register', [ShopAccountController::class, 'showRegister'])->name('shop.register');
Route::post('/shop/register', [ShopAccountController::class, 'register'])->name('shop.register.store');
Route::get('/shop/login', [ShopAccountController::class, 'showLogin'])->name('shop.login');
Route::post('/shop/login', [ShopAccountController::class, 'login'])->name('shop.login.store');
Route::post('/shop/logout', [ShopAccountController::class, 'logout'])->name('shop.logout');
Route::middleware('auth:customer')->group(function () {
    Route::get('/shop/account', [ShopAccountController::class, 'account'])->name('shop.account');
    Route::get('/shop/account/orders', [ShopAccountController::class, 'orders'])->name('shop.account.orders');
    Route::get('/shop/account/orders/{fulfillmentOrder}', [ShopAccountController::class, 'orderShow'])->name('shop.account.orders.show');
});
Route::middleware(['auth:customer', 'approved.store'])->group(function () {
    Route::get('/store', [StorePortalController::class, 'dashboard'])->name('store.dashboard');
    Route::get('/store/products', [StorePortalController::class, 'products'])->name('store.products.index');
});

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
    Route::get('/export/orders/{fulfillmentOrder}/print', [DeliveryBatchPageController::class, 'print'])->middleware('permission:export_stock')->name('export.orders.print');
    Route::patch('/export/vouchers/{voucher}/metadata', [ExportController::class, 'updateMetadata'])->middleware('permission:edit_export_metadata')->name('export.metadata.update');
    Route::get('/export/print/{id}', [ExportController::class, 'print'])->middleware('permission:export_stock')->name('export.print');

    Route::middleware('permission:delivery_access')->group(function () {
        Route::get('/delivery/orders', [DeliveryBatchPageController::class, 'ordersIndex'])->name('delivery.orders.index');
        Route::get('/delivery/orders/{fulfillmentOrder}/print', [DeliveryBatchPageController::class, 'print'])->name('delivery.orders.print');
        Route::post('/delivery/orders/{fulfillmentOrder}/deliver', [DeliveryBatchPageController::class, 'deliver'])->name('delivery.orders.confirm_deliver');
        Route::post('/delivery/orders/{fulfillmentOrder}/fail', [DeliveryBatchPageController::class, 'fail'])->name('delivery.orders.confirm_fail');
        Route::get('/delivery/batches', [DeliveryBatchPageController::class, 'batchesIndex'])->name('delivery.batches.index');
        Route::get('/delivery/batches/{deliveryBatch}', [DeliveryBatchPageController::class, 'batchesShow'])->name('delivery.batches.show');
        Route::patch('/delivery/batches/{deliveryBatch}', [DeliveryBatchPageController::class, 'updateBatch'])->middleware('permission:manage_delivery_batches')->name('delivery.batches.update');
        Route::delete('/delivery/batches/{deliveryBatch}', [DeliveryBatchPageController::class, 'cancelBatch'])->middleware('permission:manage_delivery_batches')->name('delivery.batches.cancel');
    });

    Route::resource('delivery/vehicles', DeliveryVehicleController::class)
        ->parameters(['vehicles' => 'deliveryVehicle'])
        ->except(['show'])
        ->names('delivery.vehicles')
        ->middleware('permission:manage_delivery_vehicles');

    Route::prefix('api/export')->group(function () {
        Route::get('/check-sn/{serial_number}', [ExportController::class, 'checkSerial'])->middleware('permission:export_stock')->name('export.checkSn');
        Route::post('/orders', [DeliveryBatchController::class, 'storeOrder'])->middleware('permission:export_stock')->name('export.orders.store');
        Route::post('/submit', [ExportController::class, 'store'])->middleware('permission:export_stock')->name('export.submit');
    });

    Route::prefix('api/delivery-batches')->middleware('permission:manage_delivery_batches')->group(function () {
        // Deprecated for UI: Delivery screens must not create fulfillment orders directly.
        Route::post('/orders', [DeliveryBatchController::class, 'storeOrder'])->name('delivery.orders.store');
        Route::post('/', [DeliveryBatchController::class, 'storeBatch'])->name('delivery.batches.store');
        Route::post('/{deliveryBatch}/orders', [DeliveryBatchController::class, 'addOrder'])->name('delivery.batches.orders.store');
        Route::delete('/orders/{deliveryBatchOrder}', [DeliveryBatchController::class, 'removeOrder'])->name('delivery.batches.orders.remove');
        Route::patch('/{deliveryBatch}/ready', [DeliveryBatchController::class, 'markReady'])->name('delivery.batches.ready');
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

    Route::middleware('admin.only')->group(function () {
        Route::get('/sales/customers', [CustomerPortalUserController::class, 'customers'])->name('sales.customers.index');
        Route::get('/sales/customer-accounts', [CustomerPortalUserController::class, 'index'])->name('sales.customer_accounts.index');
        Route::get('/sales/customer-accounts/{customerPortalUser}/edit', [CustomerPortalUserController::class, 'edit'])->name('sales.customer_accounts.edit');
        Route::put('/sales/customer-accounts/{customerPortalUser}', [CustomerPortalUserController::class, 'update'])->name('sales.customer_accounts.update');
    });

    Route::middleware('permission:approve_customer_orders')->group(function () {
        Route::get('/sales/order-approvals', [SalesOrderApprovalController::class, 'index'])->name('sales.order_approvals.index');
        Route::get('/sales/order-approvals/{fulfillmentOrder}', [SalesOrderApprovalController::class, 'show'])->name('sales.order_approvals.show');
        Route::post('/sales/order-approvals/{fulfillmentOrder}/approve', [SalesOrderApprovalController::class, 'approve'])->name('sales.order_approvals.approve');
        Route::post('/sales/order-approvals/{fulfillmentOrder}/reject', [SalesOrderApprovalController::class, 'reject'])->name('sales.order_approvals.reject');
    });

    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('/settings/company', [CompanyProfileController::class, 'edit'])->name('settings.company.edit');
        Route::put('/settings/company', [CompanyProfileController::class, 'update'])->name('settings.company.update');
    });
});
