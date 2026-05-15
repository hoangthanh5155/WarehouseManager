<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::all();
        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:suppliers']);
        Supplier::create($request->all());
        return redirect()->back()->with('success', 'Thêm nhà cung cấp thành công!');
    }

    /**
     * Xóa nhà cung cấp một cách an toàn.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        // Kiểm tra xem nhà cung cấp này có sản phẩm nào trong kho không
        // (Yêu cầu Model Supplier phải có relationship 'products')
        if ($supplier->products()->exists()) {
            return redirect()->back()->with('error', 'Không thể xóa! Nhà cung cấp này đang có hàng hóa trong kho.');
        }

        // Nếu không có sản phẩm liên kết, tiến hành xóa
        $supplier->delete();

        return redirect()->back()->with('success', 'Đã xóa nhà cung cấp thành công!');
    }
}