<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCatalog;
use App\Models\Supplier;
use App\Models\Location;

class ProductCatalogController extends Controller
{
    private function authorizeMasterData(): void
    {
        abort_unless(auth()->user()?->canManageMasterData(), 403, 'Bạn không có quyền truy cập chức năng này.');
    }

    /**
     * [1] Hiển thị danh sách sản phẩm mẫu
     */
    public function index()
    {
        $this->authorizeMasterData();

        // Eager Loading 'supplier' và 'products.location' để lấy thông tin vị trí từ sản phẩm thực tế
        $catalogs = ProductCatalog::with(['supplier', 'products.location'])->latest()->get();
        $suppliers = Supplier::all();
        
        return view('product-catalogs.index', compact('catalogs', 'suppliers'));
    }

    public function create()
    {
        $this->authorizeMasterData();

        $suppliers = Supplier::orderBy('name')->get();

        return view('product-catalogs.create', compact('suppliers'));
    }

    /**
     * [2] Hiển thị form chỉnh sửa sản phẩm mẫu
     */
    public function edit($id)
    {
        $this->authorizeMasterData();

        // Nạp thêm products để kiểm tra vị trí hiện tại của hàng trong kho
        $catalog = ProductCatalog::with('products')->findOrFail($id);
        $suppliers = Supplier::all();
        $locations = Location::all();

        return view('product-catalogs.edit', compact('catalog', 'suppliers', 'locations'));
    }

    public function show($id)
    {
        $this->authorizeMasterData();

        return redirect()->route('product-catalogs.edit', $id);
    }

    /**
     * [3] Thêm mới sản phẩm mẫu vào Database (Tự động tính giá theo %)
     */
    public function store(Request $request)
    {
        $this->authorizeMasterData();

        $request->validate([
            'product_name' => 'required',
            'supplier_id' => 'required',
        ], [
            'product_name.required' => 'Tên sản phẩm không được để trống.',
            'supplier_id.required' => 'Vui lòng chọn nhà cung cấp.',
        ]);

        // Loại bỏ location_id ra khỏi dữ liệu lưu vào bảng product_catalogs
        $data = $request->except(['location_id']);

        // Lấy các giá trị đầu vào (mặc định là 0 nếu để trống)
        $wholesale_price = $request->input('wholesale_price', 0);
        $agency_margin = $request->input('agency_margin', 0);
        $profit_margin = $request->input('profit_margin', 0);

        // Tự động tính toán số tiền thực tế dựa trên %
        $data['agency_price'] = $wholesale_price * (1 + ($agency_margin / 100));
        $data['retail_price'] = $wholesale_price * (1 + ($profit_margin / 100));

        ProductCatalog::create($data);

        return redirect()->back()->with('success', 'Thêm sản phẩm mẫu thành công!');
    }

    /**
     * [4] Cập nhật thông tin sản phẩm mẫu (Tự động tính lại giá theo %)
     */
    public function update(Request $request, $id)
    {
        $this->authorizeMasterData();

        $catalog = ProductCatalog::with('products')->findOrFail($id);

        $request->validate([
            'product_name' => 'required',
        ], [
            'product_name.required' => 'Tên sản phẩm không được để trống.',
        ]);

        // 1. Kiểm tra nếu người dùng có chọn vị trí kệ mới thì cập nhật hàng loạt cho hàng trong kho
        if ($request->filled('location_id')) {
            $newLocationId = $request->input('location_id');
            $catalog->products()->update(['location_id' => $newLocationId]);
        }

        // 2. Sử dụng except() để loại bỏ location_id, tránh lỗi SQL Column not found ở bảng mẫu sản phẩm
        $data = $request->except(['location_id']);

        // 3. Lấy các giá trị đầu vào để tính toán
        $wholesale_price = $request->input('wholesale_price', $catalog->wholesale_price);
        $agency_margin = $request->input('agency_margin', $catalog->agency_margin);
        $profit_margin = $request->input('profit_margin', $catalog->profit_margin);

        // 4. Tự động tính lại số tiền theo % mới
        $data['agency_price'] = $wholesale_price * (1 + ($agency_margin / 100));
        $data['retail_price'] = $wholesale_price * (1 + ($profit_margin / 100));

        $catalog->update($data);

        // Sau khi lưu xong chuyển hướng về trang danh sách
        return redirect()->route('product-catalogs.index')->with('success', 'Cập nhật thông tin và đổi vị trí hàng loạt thành công!');
    }

    /**
     * [5] Xóa sản phẩm mẫu
     */
    public function destroy($id)
    {
        $this->authorizeMasterData();

        $catalog = ProductCatalog::findOrFail($id);
        $catalog->delete();

        return redirect()->back()->with('success', 'Xóa sản phẩm mẫu thành công!');
    }
}
