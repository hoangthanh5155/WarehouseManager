@extends('layouts.admin')

@section('content')
<div class="container-fluid px-2 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary fw-bold px-3 py-2">
            ⬅️ Trở về
        </a>
        <h5 class="fw-bold text-dark m-0">CHI TIẾT MẪU SẢN PHẨM</h5>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-3" role="alert">
            <strong>🎉 Thành công!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden p-3" style="background: #fdfdfd; border-left: 4px solid #198754 !important;">
                <h6 class="fw-bold mb-3 text-success">💰 THAY ĐỔI GIÁ THEO %</h6>
                <form action="{{ route('products.showCatalog', $catalog->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="small text-muted d-block mb-1">Tên sản phẩm:</label>
                        <input type="text" class="form-control bg-light fw-bold" value="{{ $catalog->product_name }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="wholesale_price" class="small text-muted d-block mb-1">Giá nhập (Dựa theo lần nhập gần nhất):</label>
                        <input type="number" id="wholesale_price" class="form-control bg-light fw-bold text-success fs-5" 
                               value="{{ (int) $catalog->wholesale_price }}" readonly>
                        <input type="hidden" name="wholesale_price" value="{{ (int) $catalog->wholesale_price }}">
                        <small class="text-muted" style="font-size: 0.75rem;">ℹ️ Giá nhập sẽ tự động thay đổi khi bạn nhập kho lô hàng mới.</small>
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

                    <div class="mb-2 p-2 bg-light rounded-2 border">
                        <small class="text-muted d-block mb-1">Giá đại lý dự kiến:</small>
                        <span id="preview_agency_price" class="fw-bold text-primary fs-5">
                            {{ number_format($catalog->agency_price ?? 0) }} đ
                        </span>
                    </div>

                    <div class="mb-3 p-2 bg-light rounded-2 border">
                        <small class="text-muted d-block mb-1">Giá bán lẻ dự kiến:</small>
                        <span id="preview_retail_price" class="fw-bold text-danger fs-5">
                            {{ number_format($catalog->retail_price ?? 0) }} đ
                        </span>
                    </div>

                    <button type="submit" class="btn btn-success w-100 fw-bold py-2 shadow-sm">
                        💾 Lưu thay đổi phần trăm
                    </button>
                </form>
            </div>
        </div>

        <div class="col-12 col-md-8">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden p-3">
                <h6 class="fw-bold mb-3 text-dark">📦 CÁC MÃ SN ĐANG TỒN KHO ({{ $items->count() }})</h6>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th class="text-nowrap">Mã Serial (SN)</th>
                                <th class="text-nowrap">Vị trí</th>
                                <th class="text-nowrap">Nhà cung cấp</th>
                                <th class="text-nowrap">Ngày nhập</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary" style="letter-spacing: 0.5px; font-size: 0.95rem;">
                                            {{ $item->serial_number }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-primary border px-2 py-1">
                                            📍 {{ $item->location->shelf_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td><span class="text-dark">{{ $item->supplier->name ?? 'N/A' }}</span></td>
                                    <td class="text-nowrap text-muted" style="font-size: 0.8rem;">
                                        {{ $item->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">📭 Sản phẩm này hiện đã hết hàng trong kho.</td>
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