<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class LocationController extends Controller
{
    private function authorizeMasterData(): void
    {
        abort_unless(auth()->user()?->canManageMasterData(), 403, 'Bạn không có quyền truy cập chức năng này.');
    }

    /**
     * [1] Hiển thị danh sách vị trí kệ
     */
    public function index()
    {
        $this->authorizeMasterData();

        $locations = Location::latest()->get();
        return view('locations.index', compact('locations'));
    }

    public function create()
    {
        $this->authorizeMasterData();

        return view('locations.create');
    }

    /**
     * [2] Thêm mới vị trí kệ vào Database (Hỗ trợ AJAX và Redirect)
     */
    public function store(Request $request)
    {
        $this->authorizeMasterData();

        $request->validate([
            'shelf_name' => 'required|unique:locations,shelf_name'
        ], [
            'shelf_name.required' => 'Tên vị trí kệ không được để trống.',
            'shelf_name.unique' => 'Vị trí kệ này đã tồn tại.'
        ]);

        // ĐÃ FIX: Chỉ lấy duy nhất trường shelf_name để tạo mới trong Database
        $location = Location::create($request->only(['shelf_name']));

        // Kiểm tra xem yêu cầu gửi lên có phải là AJAX không
        if ($request->ajax() || $request->has('is_ajax')) {
            return response()->json([
                'status' => 'success',
                'message' => 'Thêm vị trí kệ mới thành công!',
                'data' => $location
            ]);
        }

        return redirect()->back()->with('success', 'Thêm vị trí kệ thành công!');
    }

    /**
     * [3] Cập nhật thông tin vị trí kệ
     */
    public function edit($id)
    {
        $this->authorizeMasterData();

        $location = Location::findOrFail($id);

        return view('locations.edit', compact('location'));
    }

    public function show($id)
    {
        $this->authorizeMasterData();

        return redirect()->route('locations.edit', $id);
    }

    public function update(Request $request, $id)
    {
        $this->authorizeMasterData();

        $location = Location::findOrFail($id);

        $request->validate([
            'shelf_name' => 'required|unique:locations,shelf_name,' . $id
        ], [
            'shelf_name.required' => 'Tên vị trí kệ không được để trống.',
            'shelf_name.unique' => 'Vị trí kệ này đã tồn tại.'
        ]);

        // Tương tự, chỉ cho phép cập nhật shelf_name
        $location->update($request->only(['shelf_name']));

        return redirect()->back()->with('success', 'Cập nhật vị trí kệ thành công!');
    }

    /**
     * [4] Xóa vị trí kệ an toàn
     */
    public function destroy($id)
    {
        $this->authorizeMasterData();

        $location = Location::findOrFail($id);

        // Chặn xóa nếu trong kệ vẫn còn sản phẩm
        if ($location->products()->exists()) {
            return redirect()->back()->with('error', 'Không thể xóa! Vị trí kệ này vẫn đang chứa sản phẩm.');
        }

        $location->delete();

        return redirect()->back()->with('success', 'Xóa vị trí kệ thành công!');
    }
}
