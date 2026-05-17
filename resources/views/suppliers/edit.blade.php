@extends('layouts.admin')

@section('content')
<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4 mt-2">
        <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light bg-white border rounded-pill fw-bold px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i>Trở về danh sách
        </a>
        <div class="text-primary small fw-bold text-uppercase">Quản lý kho</div>
        <h4 class="fw-bold text-dark m-0">Sửa nhà cung cấp</h4>
        <div class="text-muted small">Cập nhật tên nhà cung cấp trong hệ thống</div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="fw-bold small text-secondary mb-1">Tên nhà cung cấp</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name) }}" required>
                    @error('name')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <button type="submit" class="btn btn-primary fw-bold">
                    <i class="bi bi-save me-1"></i>Cập nhật nhà cung cấp
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
