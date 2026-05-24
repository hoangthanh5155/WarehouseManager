<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductCatalog;
use App\Models\Location;
use App\Services\Warehouse\ImportStockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use RespondsWithApi;

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
    public function storeManual(Request $request, ImportStockService $importStockService)
    {
        if (!$request->has('is_ajax') && !$request->has('is_ajax_tab3')) {
            return redirect()->back()->with('success', 'Thao tác thành công!');
        }

        try {
            $payload = $request->validate([
                'supplier_id' => ['required'],
                'product_catalog_id' => ['required'],
                'location_id' => ['required'],
                'wholesale_price' => ['nullable', 'numeric', 'min:0'],
                'scanned_sn' => ['nullable', 'string', 'max:255'],
                'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $result = $request->has('is_ajax')
                ? $importStockService->importScannedSerial([
                    ...$payload,
                    'serial_number' => $payload['scanned_sn'] ?? null,
                ], $request->user()?->id)
                : $importStockService->importGeneratedSerials($payload, $request->user()?->id);

            return $this->successResponse('Nhập kho thành công.', $result);
        } catch (ValidationException $e) {
            return $this->errorResponse(
                collect($e->errors())->flatten()->first() ?: 'Dữ liệu nhập kho không hợp lệ.',
                $e->errors(),
                422
            );
        } catch (\Throwable $e) {
            report($e);

            return $this->errorResponse('Lỗi xử lý nhập kho: ' . $e->getMessage(), [], 500);
        }
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
