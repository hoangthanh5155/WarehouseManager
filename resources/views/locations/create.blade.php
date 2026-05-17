@extends('layouts.admin')

@section('content')
<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 mt-2">
        <a href="{{ route('locations.index') }}" class="btn btn-sm btn-light bg-white border rounded-pill fw-bold px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i>Trở về danh sách
        </a>
        <div class="text-primary small fw-bold text-uppercase">Quản lý kho</div>
        <h4 class="fw-bold text-dark m-0">Thêm vị trí kệ</h4>
        <div class="text-muted small">Tạo vị trí lưu trữ hàng hóa trong kho</div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('locations.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="fw-bold small text-secondary mb-1">Tên kệ / vị trí</label>
                    <input type="text" name="shelf_name" class="form-control" value="{{ old('shelf_name') }}" required>
                    @error('shelf_name')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="bi bi-save me-1"></i>Lưu vị trí
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
