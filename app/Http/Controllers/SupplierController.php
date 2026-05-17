<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class SupplierController extends Controller
{
    private function authorizeMasterData(): void
    {
        abort_unless(auth()->user()?->canManageMasterData(), 403, 'Bạn không có quyền truy cập chức năng này.');
    }

    public function index()
    {
        $this->authorizeMasterData();

        $suppliers = Supplier::all();
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        $this->authorizeMasterData();

        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $this->authorizeMasterData();

        $request->validate(['name' => 'required|unique:suppliers']);
        Supplier::create($request->all());
        return redirect()->back()->with('success', 'Thêm nhà cung cấp thành công!');
    }

    public function edit(Supplier $supplier)
    {
        $this->authorizeMasterData();

        return view('suppliers.edit', compact('supplier'));
    }

    public function show(Supplier $supplier)
    {
        $this->authorizeMasterData();

        return redirect()->route('suppliers.edit', $supplier);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeMasterData();

        $request->validate(['name' => 'required|unique:suppliers,name,' . $supplier->id]);
        $supplier->update($request->only('name'));

        return redirect()->route('suppliers.index')->with('success', 'Cập nhật nhà cung cấp thành công!');
    }

    /**
     * Xóa nhà cung cấp một cách an toàn.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorizeMasterData();

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
