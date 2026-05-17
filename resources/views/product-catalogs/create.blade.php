@extends('layouts.admin')

@section('content')
<style>
    .master-page-header {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 1rem;
    }
    .page-kicker { color: #0d6efd; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
</style>

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="master-page-header mb-4 mt-2">
        <a href="{{ route('product-catalogs.index') }}" class="btn btn-sm btn-light bg-white border rounded-pill fw-bold px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i>Trở về danh sách
        </a>
        <div class="page-kicker">Quản lý kho</div>
        <h4 class="fw-bold text-dark m-0">Thêm danh mục sản phẩm</h4>
        <div class="text-muted small">Tạo mẫu sản phẩm để dùng trong nhập kho và tra cứu hàng hóa</div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-triangle me-1"></i>Vui lòng kiểm tra lại thông tin nhập.
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 bg-white">
        <div class="card-body p-3 p-md-4">
            <form action="{{ route('product-catalogs.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label class="fw-bold small text-secondary mb-1">Tên sản phẩm</label>
                        <input type="text" name="product_name" class="form-control" value="{{ old('product_name') }}" required>
                        @error('product_name')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-12">
                        <label class="fw-bold small text-secondary mb-1">Nhà cung cấp</label>
                        <select name="supplier_id" class="form-select" required>
                            <option value="">Chọn nhà cung cấp</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-secondary mb-1">Giá sỉ</label>
                        <input type="number" step="0.01" name="wholesale_price" class="form-control" value="{{ old('wholesale_price', 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-secondary mb-1">% biên đại lý</label>
                        <input type="number" step="0.01" name="agency_margin" class="form-control" value="{{ old('agency_margin', 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-bold small text-secondary mb-1">% biên bán lẻ</label>
                        <input type="number" step="0.01" name="profit_margin" class="form-control" value="{{ old('profit_margin', 0) }}">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary fw-bold mt-4">
                    <i class="bi bi-save me-1"></i>Lưu danh mục
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
