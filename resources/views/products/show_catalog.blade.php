@extends('layouts.admin')

@section('content')
<style>
    .catalog-page-header {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 1rem;
    }
    .page-kicker { color: #0d6efd; font-size: 0.78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
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
    .price-stat {
        background: #f8fafc;
        border: 1px solid #e9eef5;
        border-radius: .9rem;
        padding: .85rem;
    }
    .empty-state {
        min-height: 180px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: .5rem;
    }
</style>

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="catalog-page-header mb-4 mt-2">
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-light bg-white border rounded-pill fw-bold px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i>Trở về danh sách
        </a>
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box d-none d-sm-inline-flex">
                <i class="bi bi-box-seam fs-5"></i>
            </div>
            <div>
                <div class="page-kicker">Quản lý sản phẩm</div>
                <h4 class="fw-bold text-dark m-0">Chi tiết mẫu sản phẩm</h4>
                <div class="text-muted small">Cập nhật biên lợi nhuận và xem serial còn tồn</div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
            <i class="bi bi-check-circle me-1"></i><strong>Đã cập nhật thành công.</strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden p-3 p-md-4 bg-white">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-box" style="background:#eaf7ef;color:#198754;">
                        <i class="bi bi-percent fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Thiết lập giá bán</h6>
                        <div class="text-muted small">Điều chỉnh theo phần trăm lợi nhuận</div>
                    </div>
                </div>
                <form action="{{ route('products.showCatalog', $catalog->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1">Tên sản phẩm:</label>
                        <input type="text" class="form-control bg-light fw-bold" value="{{ $catalog->product_name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="wholesale_price" class="small text-muted d-block mb-1">Giá nhập:</label>
                        <input type="number" id="wholesale_price" class="form-control bg-light fw-bold text-success fs-5" 
                               value="{{ (int) $catalog->wholesale_price }}" readonly>
                        <input type="hidden" name="wholesale_price" value="{{ (int) $catalog->wholesale_price }}">
                        <small class="text-muted" style="font-size: 0.75rem;">Giá nhập tự cập nhật theo lần nhập kho gần nhất.</small>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="agency_margin" class="small text-muted d-block mb-1">Đại lý (+ %):</label>
                            <input type="number" step="0.1" name="agency_margin" id="agency_margin" class="form-control fw-bold text-primary fs-5" 
                                   value="{{ $catalog->agency_margin ?? 0 }}" placeholder="VD: 5">
                        </div>
                        <div class="col-6">
                            <label for="profit_margin" class="small text-muted d-block mb-1">Khách lẻ (+ %):</label>
                            <input type="number" step="0.1" name="profit_margin" id="profit_margin" class="form-control fw-bold text-danger fs-5" 
                                   value="{{ $catalog->profit_margin ?? 0 }}" placeholder="VD: 12">
                        </div>
                    </div>

                    <div class="price-stat mb-2">
                        <small class="text-muted d-block mb-1">Giá đại lý dự kiến:</small>
                        <span id="preview_agency_price" class="fw-bold text-primary fs-5">
                            {{ number_format($catalog->agency_price ?? 0) }} đ
                        </span>
                    </div>

                    <div class="price-stat mb-3">
                        <small class="text-muted d-block mb-1">Giá bán lẻ dự kiến:</small>
                        <span id="preview_retail_price" class="fw-bold text-danger fs-5">
                            {{ number_format($catalog->retail_price ?? 0) }} đ
                        </span>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                        <i class="bi bi-save me-1"></i>Lưu thiết lập giá
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden p-3 p-md-4 bg-white">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-box-seam me-1 text-primary"></i>Serial đang tồn kho</h6>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">{{ $items->count() }}</span>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap">Mã Serial (SN)</th>
                                <th class="text-nowrap">Trạng thái</th>
                                <th class="text-nowrap">Vị trí</th>
                                <th class="text-nowrap">Nhà cung cấp</th>
                                <th class="text-nowrap">Ngày nhập</th>
                                <th class="text-nowrap">Đã tồn kho</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('serial.trace.search', ['serial_number' => $item->serial_number]) }}" class="fw-bold text-primary text-decoration-none" style="letter-spacing: 0.5px; font-size: 0.95rem;">
                                            {{ $item->serial_number }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Còn trong kho</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border px-2 py-1">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $item->location->shelf_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td><span class="text-dark">{{ $item->supplier->name ?? 'N/A' }}</span></td>
                                    <td class="text-nowrap text-muted" style="font-size: 0.8rem;">
                                        {{ $item->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="text-nowrap text-muted" style="font-size: 0.8rem;">
                                        {{ $item->created_at->diffForHumans(now(), true) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <div class="empty-state">
                                            <i class="bi bi-inboxes fs-1 text-secondary"></i>
                                            <div>Sản phẩm này hiện đã hết hàng trong kho.</div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/products/pricing.js'])
@endpush
