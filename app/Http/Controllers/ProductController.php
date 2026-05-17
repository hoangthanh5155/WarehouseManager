<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductCatalog;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    // [1] Trang chủ danh sách sản phẩm
    public function index(Request $request)
    {
        $supplierId = $request->query('supplier_id');
        $sort = $request->query('sort', 'featured');
        $allowedSorts = ['featured', 'newest', 'stock_desc', 'stock_asc', 'price_asc', 'price_desc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'featured';
        }

        $suppliers = Supplier::orderBy('name')->get();
        $relations = ['supplier', 'location'];
        if ($request->user()?->canViewCostPrices()) {
            $relations[] = 'productCatalog';
        } else {
            $relations['productCatalog'] = function ($query) {
                $query->select('id', 'product_name', 'retail_price', 'agency_price');
            };
        }

        $productsQuery = Product::query()
            ->leftJoin('product_catalogs as catalogs', 'products.product_catalog_id', '=', 'catalogs.id')
            ->select(
                'products.product_catalog_id',
                'products.supplier_id',
                'products.location_id',
                DB::raw('count(*) as total_qty'),
                DB::raw('MAX(products.created_at) as latest_imported_at'),
                DB::raw('MAX(catalogs.retail_price) as sort_retail_price')
            )
            ->with($relations)
            ->where('products.status', 1)
            ->when($supplierId, function ($query, $supplierId) {
                return $query->where('products.supplier_id', $supplierId);
            })
            ->groupBy('products.product_catalog_id', 'products.supplier_id', 'products.location_id');

        match ($sort) {
            'newest' => $productsQuery->orderByDesc('latest_imported_at'),
            'stock_asc' => $productsQuery->orderBy('total_qty', 'asc'),
            'price_asc' => $productsQuery->orderBy('sort_retail_price', 'asc'),
            'price_desc' => $productsQuery->orderByDesc('sort_retail_price'),
            default => $productsQuery->orderByDesc('total_qty'),
        };

        $products = $productsQuery
            ->paginate(20)
            ->withQueryString();

        return view('products.index', compact('products', 'suppliers', 'supplierId', 'sort'));
    }

    // [2] Xem chi tiết 1 sản phẩm & Cập nhật giá đa cấp thông minh theo %
    public function showCatalog(Request $request, $id)
    {
        abort_unless($request->user()?->canAccessFullProductDetail(), 403);

        $catalog = ProductCatalog::findOrFail($id);

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $wholesale_price = $request->wholesale_price ?: 0;
            $agency_margin = $request->agency_margin ?: 0; 
            $profit_margin = $request->profit_margin ?: 0;

            $agency_price = $wholesale_price * (1 + ($agency_margin / 100));
            $retail_price = $wholesale_price * (1 + ($profit_margin / 100));

            $catalog->update([
                'wholesale_price' => $wholesale_price,
                'agency_margin'   => $agency_margin,
                'profit_margin'   => $profit_margin,
                'agency_price'    => $agency_price,
                'retail_price'    => $retail_price,
            ]);

            return redirect()->back()->with('success', 'Đã cập nhật bảng giá và tỷ lệ % thành công!');
        }

        $items = Product::where('product_catalog_id', $id)
            ->where('status', 1)
            ->with(['location', 'supplier'])
            ->latest()
            ->get();

        return view('products.show_catalog', compact('catalog', 'items'));
    }

    // [3] Giao diện Nhập kho
    public function import()
    {
        $suppliers = Supplier::all();
        $locations = Location::all();
        return view('products.import', compact('suppliers', 'locations'));
    }

    // [4] Xử lý lưu hàng hóa nhập kho (Đã rạch ròi 2 Tabs & Chống trùng SN)
    public function storeManual(Request $request)
    {
        // 1. Phân tích Nhà Cung Cấp
        $supplier_id = $request->supplier_id;
        if (!is_numeric($supplier_id) && !empty($supplier_id)) {
            $newSupplier = Supplier::firstOrCreate(['name' => $supplier_id]);
            $supplier_id = $newSupplier->id;
        }

        $wholesale_price = $request->wholesale_price ?? 0;

        // 2. Phân tích Sản Phẩm (Danh mục)
        $product_catalog_id = $request->product_catalog_id;
        if (!empty($product_catalog_id)) {
            if (!is_numeric($product_catalog_id)) {
                $existingCatalog = ProductCatalog::where('product_name', $product_catalog_id)
                    ->where('supplier_id', $supplier_id)
                    ->first();
                                                                             
                if ($existingCatalog) {
                    $product_catalog_id = $existingCatalog->id;
                    if ($wholesale_price > 0) {
                        $new_agency_price = $wholesale_price * (1 + ($existingCatalog->agency_margin / 100));
                        $new_retail_price = $wholesale_price * (1 + ($existingCatalog->profit_margin / 100));

                        $existingCatalog->update([
                            'wholesale_price' => $wholesale_price,
                            'agency_price'    => $new_agency_price,
                            'retail_price'    => $new_retail_price,
                        ]);
                    }
                } else {
                    $prefix = strtoupper(substr($product_catalog_id, 0, 3)) . '-' . rand(1000, 9999);
                    $newCatalog = ProductCatalog::create([
                        'product_name' => $product_catalog_id,
                        'supplier_id' => $supplier_id,
                        'model_prefix' => $prefix,
                        'wholesale_price' => $wholesale_price,
                        'agency_margin' => 0,
                        'profit_margin' => 0,
                        'agency_price' => $wholesale_price,
                        'retail_price' => $wholesale_price
                    ]);
                    $product_catalog_id = $newCatalog->id;
                }
            } else {
                $existingCatalog = ProductCatalog::find($product_catalog_id);
                if ($existingCatalog && $wholesale_price > 0) {
                    $new_agency_price = $wholesale_price * (1 + ($existingCatalog->agency_margin / 100));
                    $new_retail_price = $wholesale_price * (1 + ($existingCatalog->profit_margin / 100));

                    $existingCatalog->update([
                        'wholesale_price' => $wholesale_price,
                        'agency_price'    => $new_agency_price,
                        'retail_price'    => $new_retail_price,
                    ]);
                }
            }
        }

        // 3. Phân tích Vị trí Kệ
        $location_id = $request->location_id;
        if (!is_numeric($location_id) && !empty($location_id)) {
            $newLoc = Location::firstOrCreate(['shelf_name' => $location_id]);
            $location_id = $newLoc->id;
        }

        // ==========================================
        // CHIA LUỒNG XỬ LÝ CHÍNH XÁC THEO TỪNG TAB
        // ==========================================

        // luồng 1: TAB 1 - Quét mã SN lưu tự động qua AJAX
        if ($request->has('is_ajax')) {
            $sn = trim($request->scanned_sn);
            
            if (empty($sn)) {
                return response()->json(['status' => 'error', 'message' => 'Mã SN không được để trống!']);
            }

            if (Product::where('serial_number', $sn)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'Mã SN này đã tồn tại trong kho!']);
            }

            Product::create([
                'product_catalog_id' => $product_catalog_id,
                'supplier_id' => $supplier_id,
                'location_id' => $location_id,
                'serial_number' => $sn,
                'status' => 1
            ]);

            return response()->json(['status' => 'success']);
        } 
        
        // luồng 2: TAB 2 - Tạo mã hàng loạt & In tem qua AJAX
        if ($request->has('is_ajax_tab3')) {
            $newProducts = []; 
            $quantity = intval($request->input('quantity', 1));
            
            if ($quantity > 100) {
                return response()->json(['status' => 'error', 'message' => 'Chỉ hỗ trợ tạo tối đa 100 mã một lần!']);
            }

            $catalog = ProductCatalog::find($product_catalog_id);
            $productName = $catalog ? $catalog->product_name : 'Sản phẩm mới';

            if (!$supplier_id || !$product_catalog_id || !$location_id) {
                return response()->json(['status' => 'error', 'message' => 'Thiếu thông tin nhà cung cấp, sản phẩm hoặc vị trí kệ.']);
            }

            for ($i = 0; $i < $quantity; $i++) {
                do {
                    $code = 'SN' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
                } while (Product::where('serial_number', $code)->exists());
                
                Product::create([
                    'product_catalog_id' => $product_catalog_id,
                    'supplier_id' => $supplier_id,
                    'location_id' => $location_id,
                    'serial_number' => $code,
                    'status' => 1
                ]);
                
                $newProducts[] = [
                    'sn' => $code,
                    'name' => $productName
                ];
            }
            
            return response()->json([
                'status' => 'success',
                'print_items' => $newProducts
            ]);
        }

        return redirect()->back()->with('success', 'Thao tác thành công!');
    }

    // [5] API Lấy gợi ý thông minh (Lấy chính xác Vị Trí Kệ Cũ)
    public function smartSuggestion(Request $request)
    {
        $productName = $request->product_name;
        $catalogId = $request->catalog_id;

        $catalog = null;
        if ($catalogId && is_numeric($catalogId)) {
            $catalog = ProductCatalog::find($catalogId);
        }
        
        if (!$catalog && $productName) {
            $catalog = ProductCatalog::where('product_name', $productName)->first();
        }
        
        if (!$catalog) {
            return response()->json(['status' => 'not_found']);
        }

        // Tự động tìm vị trí kệ của sản phẩm thực tế đã nhập gần nhất
        $lastProduct = Product::where('product_catalog_id', $catalog->id)
            ->where('status', 1)
            ->whereNotNull('location_id')
            ->where('location_id', '>', 0)
            ->with('location')
            ->latest('id')
            ->first();

        $payload = [
            'status' => 'success',
            'retail_price' => (float) $catalog->retail_price,
            'location' => ($lastProduct && $lastProduct->location) ? $lastProduct->location->shelf_name : ''
        ];

        if ($request->user()?->canViewCostPrices()) {
            $payload['wholesale_price'] = (float) $catalog->wholesale_price;
        }

        return response()->json($payload);
    }
}
