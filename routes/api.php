<?php

use App\Http\Controllers\ExportController;

// API kiểm tra mã SN trong kho
Route::get('/export/check-sn/{serial_number}', [ExportController::class, 'checkSerial']);

// API lưu đơn chính + đơn mở rộng
Route::post('/export/submit', [ExportController::class, 'store']);