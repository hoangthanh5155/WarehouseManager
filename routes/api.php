<?php

// NOTE: This file is not currently registered in bootstrap/app.php.
// The active Export API routes run through routes/web.php with the api/export
// prefix and web/auth/permission middleware.
// Do not enable/register this file without reviewing middleware coverage first.

use App\Http\Controllers\ExportController;

// API kiểm tra mã SN trong kho
Route::get('/export/check-sn/{serial_number}', [ExportController::class, 'checkSerial']);

// API lưu đơn chính + đơn mở rộng
Route::post('/export/submit', [ExportController::class, 'store']);
