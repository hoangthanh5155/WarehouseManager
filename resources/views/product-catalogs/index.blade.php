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
    .icon-box {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e7f1ff;
        color: #0d6efd;
        flex: 0 0 auto;
    }
    .mobile-catalog-card { display: none; }
    @media (max-width: 767.98px) {
        .desktop-catalog-table { display: none; }
        .mobile-catalog-card { display: flex; }
    }
</style>

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="master-page-header d-flex flex-column flex-md-row justify-content-between gap-3 mb-4 mt-2">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box d-none d-sm-inline-flex">
                <i class="bi bi-boxes fs-5"></i>
            </div>
            <div>
                <div class="page-kicker">Quản lý kho</div>
                <h4 class="fw-bold text-dark m-0">Danh mục sản phẩm</h4>
                <div class="text-muted small">Quản lý mẫu sản phẩm, nhà cung cấp và vị trí mặc định</div>
            </div>
        </div>
        <a href="{{ route('product-catalogs.create') }}" class="btn btn-primary fw-bold rounded-3 align-self-start align-self-md-center">
            <i class="bi bi-plus-circle me-1"></i>Thêm sản phẩm
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white desktop-catalog-table">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: .95rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tên sản phẩm</th>
                        <th>Nhà cung cấp</th>
                        <th>Vị trí kệ</th>
                        <th class="text-end pe-3" style="width: 150px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($catalogs as $cat)
                        @php
                            $locations = $cat->inventory_location_names ?: '';
                            $hasInventoryProducts = (int) ($cat->inventory_product_count ?? 0) > 0;
                        @endphp
                        <tr>
                            <td class="ps-3"><strong class="text-dark">{{ $cat->product_name }}</strong></td>
                            <td class="text-muted">{{ $cat->supplier ? $cat->supplier->name : 'N/A' }}</td>
                            <td class="text-primary fw-semibold">
                                {{ $locations ?: ($hasInventoryProducts ? 'Chưa gắn vị trí' : 'Hết hàng / Chưa nhập') }}
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('product-catalogs.edit', $cat->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="bi bi-pencil-square me-1"></i>Sửa
                                    </a>
                                    <form action="{{ route('product-catalogs.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">
                                            <i class="bi bi-trash me-1"></i>Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-inboxes d-block fs-2 mb-2"></i>
                                Chưa có dữ liệu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mobile-catalog-card flex-column gap-3">
        @forelse($catalogs as $cat)
            @php
                $locations = $cat->inventory_location_names ?: '';
                $hasInventoryProducts = (int) ($cat->inventory_product_count ?? 0) > 0;
            @endphp
            <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
                <div class="fw-bold text-dark mb-1">{{ $cat->product_name }}</div>
                <div class="small text-muted mb-2">{{ $cat->supplier ? $cat->supplier->name : 'N/A' }}</div>
                <div class="small text-primary fw-semibold mb-3">
                    <i class="bi bi-geo-alt me-1"></i>{{ $locations ?: ($hasInventoryProducts ? 'Chưa gắn vị trí' : 'Hết hàng / Chưa nhập') }}
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('product-catalogs.edit', $cat->id) }}" class="btn btn-sm btn-outline-primary fw-bold flex-fill">
                        <i class="bi bi-pencil-square me-1"></i>Sửa
                    </a>
                    <form action="{{ route('product-catalogs.destroy', $cat->id) }}" method="POST" class="flex-fill" onsubmit="return confirm('Bạn chắc chắn muốn xóa?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold w-100">
                            <i class="bi bi-trash me-1"></i>Xóa
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 text-center text-muted">
                <i class="bi bi-inboxes fs-2 mb-2"></i>
                Chưa có dữ liệu.
            </div>
        @endforelse
    </div>
</div>
@endsection
