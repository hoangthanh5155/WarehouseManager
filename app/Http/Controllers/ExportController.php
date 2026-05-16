<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\ExportVoucher;
use App\Models\ProductCatalog;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ExportController extends Controller
{
    /**
     * 1. Hiển thị giao diện Tạo đơn xuất kho
     */
    public function index()
    {
        try {
            $customers = Customer::orderBy('name', 'asc')->get();

            // Lấy danh mục sản phẩm và đếm số lượng tồn có status = 1 (trong kho)
            $productCatalogs = ProductCatalog::withCount(['products' => function ($query) {
                $query->where('status', 1);
            }])
            ->orderBy('product_name', 'asc')
            ->get();

            // 💡 ĐÃ SỬA: Trỏ chính xác vào views/exports/create.blade.php
            $recentVouchers = ExportVoucher::orderByDesc('exported_at')
                ->limit(8)
                ->get();

            return view('exports.create', compact('customers', 'productCatalogs', 'recentVouchers'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi hiển thị giao diện: ' . $e->getMessage() . ' tại dòng ' . $e->getLine()
            ], 500);
        }
    }

    /**
     * 2. API: Kiểm tra mã SN trước khi cho phép thêm vào đơn
     */
    public function checkSerial(Request $request, $serial_number)
    {
        try {
            $productId = $request->query('product_id');

            $item = Product::where('serial_number', $serial_number)
                ->where('product_catalog_id', $productId)
                ->where('status', 1)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mã Serial không tồn tại trong kho hoặc đã được xuất bán!'
                ], 404);
            }

            $catalog = ProductCatalog::find($productId);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'serial_number' => $item->serial_number,
                    'wholesale_price' => $catalog ? (float) $catalog->wholesale_price : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kiểm tra Serial: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 3. Xử lý Lưu Đơn chính & Đơn mở rộng vào Database
     */
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'export_type' => 'required|string',
            'customer_type' => 'required|string',
            'buyer_name' => 'required|string',
            'main_items' => 'required|array|min:1', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ: ' . implode(', ', $validator->errors()->all())
            ], 422);
        }

        DB::beginTransaction();
        try {
            $now = now();
            $sellerName = $request->filled('seller_name') ? $request->seller_name : config('app.name');
            $sellerTaxCode = $request->filled('seller_tax_code') ? $request->seller_tax_code : '';
            $sellerAddress = $request->filled('seller_address') ? $request->seller_address : '';
            $sellerPhone = $request->filled('seller_phone') ? $request->seller_phone : '';
            
            $customerId = $request->customer_id;
            if (!$customerId && !empty($request->buyer_name)) {
                $newCustomer = Customer::create([
                    'name' => $request->buyer_name,
                    'company_name' => $request->company_name,
                    'address' => $request->address,
                    'tax_code' => $request->tax_code,
                    'type' => $request->customer_type
                ]);
                $customerId = $newCustomer->id;
            }

            // --------------------------------------------------
            // BƯỚC A: LƯU ĐƠN HÀNG CHÍNH
            // --------------------------------------------------
            $mainExportCode = 'PX-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            
            $mainTotalCost = 0;
            $mainTotalAmount = 0;
            foreach ($request->main_items as $item) {
                $catalog = ProductCatalog::find($item['product_id']);
                if (!$catalog) {
                    throw new \Exception('Không tìm thấy sản phẩm có ID: ' . $item['product_id'] . ' trong danh mục.');
                }
                $wholesale = (float) $catalog->wholesale_price;

                $mainTotalCost += ($wholesale * $item['quantity']);
                $mainTotalAmount += ($item['price'] * $item['quantity']);
            }

            $mainItemsJson = is_array($request->main_items) ? json_encode($request->main_items, JSON_UNESCAPED_UNICODE) : $request->main_items;

            $mainVoucher = ExportVoucher::create([
                'parent_id' => null, 
                'export_code' => $mainExportCode,
                'export_type' => $request->export_type,
                'customer_type' => $request->customer_type,
                'seller_name' => $sellerName,
                'seller_tax_code' => $sellerTaxCode,
                'seller_address' => $sellerAddress,
                'seller_phone' => $sellerPhone,
                'customer_id' => $customerId,
                'buyer_name' => $request->buyer_name,
                'company_name' => $request->company_name,
                'address' => $request->address,
                'tax_code' => $request->tax_code,
                'items' => $mainItemsJson, 
                'total_cost' => $mainTotalCost,
                'total_amount' => $mainTotalAmount,
                'note' => $request->note,
                'exported_at' => $now
            ]);

            // Cập nhật trạng thái 'sold' (2) cho các mã SN của đơn chính
            foreach ($request->main_items as $item) {
                if (!empty($item['serials'])) {
                    $updated = Product::whereIn('serial_number', $item['serials'])
                        ->update(['status' => 2, 'updated_at' => $now]);

                    if ($updated === 0) {
                        throw new \Exception('Không thể cập nhật trạng thái mã SN của đơn chính. Vui lòng kiểm tra lại mã SN.');
                    }
                }
            }

            // --------------------------------------------------
            // BƯỚC B: LƯU CÁC ĐƠN MỞ RỘNG (Nếu có)
            // --------------------------------------------------
            $subVoucherIds = [];
            if ($request->has('sub_vouchers') && is_array($request->sub_vouchers)) {
                foreach ($request->sub_vouchers as $index => $sub) {
                    if (empty($sub['items'])) continue; 

                    $subExportCode = $mainExportCode . '-MR' . ($index + 1);
                    $subTotalCost = 0;
                    $subTotalAmount = 0;

                    foreach ($sub['items'] as $item) {
                        $catalog = ProductCatalog::find($item['product_id']);
                        if (!$catalog) {
                            throw new \Exception('Không tìm thấy sản phẩm có ID: ' . $item['product_id'] . ' trong đơn mở rộng.');
                        }
                        $wholesale = (float) $catalog->wholesale_price;

                        $subTotalCost += ($wholesale * $item['quantity']);
                        $subTotalAmount += ($item['price'] * $item['quantity']);
                    }

                    $subItemsJson = is_array($sub['items']) ? json_encode($sub['items'], JSON_UNESCAPED_UNICODE) : $sub['items'];

                    $subVoucher = ExportVoucher::create([
                        'parent_id' => $mainVoucher->id, 
                        'export_code' => $subExportCode,
                        'export_type' => $request->export_type,
                        'customer_type' => $request->customer_type,
                        'seller_name' => $sellerName,
                        'seller_tax_code' => $sellerTaxCode,
                        'seller_address' => $sellerAddress,
                        'seller_phone' => $sellerPhone,
                        'customer_id' => $customerId,
                        'buyer_name' => $request->buyer_name,
                        'company_name' => $request->company_name,
                        'address' => $request->address,
                        'tax_code' => $request->tax_code,
                        'items' => $subItemsJson,
                        'total_cost' => $subTotalCost,
                        'total_amount' => $subTotalAmount,
                        'note' => $sub['note'] ?? 'Đơn mở rộng của ' . $mainExportCode,
                        'exported_at' => $now
                    ]);

                    // Cập nhật trạng thái cho các mã SN của đơn mở rộng
                    foreach ($sub['items'] as $item) {
                        if (!empty($item['serials'])) {
                            Product::whereIn('serial_number', $item['serials'])
                                ->update(['status' => 2, 'updated_at' => $now]);
                        }
                    }

                    $subVoucherIds[] = $subVoucher->id;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lưu đơn xuất kho thành công!',
                'main_voucher_id' => $mainVoucher->id,
                'sub_voucher_ids' => $subVoucherIds
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi xử lý Database: ' . $e->getMessage() . ' tại dòng ' . $e->getLine()
            ], 500);
        }
    }

    /**
     * 4. Hiển thị trang in A4
     */
    public function print($id)
    {
        try {
            $voucher = ExportVoucher::findOrFail($id);

            $subVouchers = [];
            if ($voucher->parent_id === null) {
                $subVouchers = ExportVoucher::where('parent_id', $voucher->id)->get();
            }

            // 💡 ĐÃ SỬA: Trỏ chính xác vào views/exports/print.blade.php
            return view('exports.print', compact('voucher', 'subVouchers'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Không tìm thấy hóa đơn cần in: ' . $e->getMessage());
        }
    }

    public function updateMetadata(Request $request, ExportVoucher $voucher)
    {
        $validated = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:255'],
        ]);

        $voucher->update($validated);

        return redirect()->route('export.index')->with('success', 'Đã cập nhật thông tin hóa đơn.');
    }
}
