@extends('layouts.admin')

@section('content')
<style>
    /* CSS Tối ưu tìm kiếm và giao diện Mobile */
    .search-box {
        border: 2px solid #0d6efd;
        border-radius: 10px;
        padding-left: 40px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%230d6efd' class='bi bi-search' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 15px center;
    }
    
    .product-item {
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        border-left: 4px solid #0d6efd !important;
        text-decoration: none !important;
    }
    
    .product-item:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
    }

    .mobile-price {
        font-size: 1.05rem;
        color: #dc3545;
        font-weight: 700;
    }
    
    .mobile-location {
        font-size: 0.85rem;
        background-color: #e8f0fe;
        color: #1a73e8;
        padding: 3px 8px;
        border-radius: 6px;
        font-weight: 600;
        display: inline-block;
    }

    .total-qty-badge {
        font-size: 0.85rem;
        background-color: #e6fcf5;
        color: #0ca678;
        padding: 3px 8px;
        border-radius: 6px;
        font-weight: 600;
        display: inline-block;
    }
</style>

<div class="container-fluid px-2 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <h4 class="fw-bold text-dark m-0">📱 TRA CỨU HÀNG HÓA</h4>
        <a href="{{ route('products.import') }}" class="btn btn-sm btn-primary fw-bold px-3 py-2">📥 Nhập mới</a>
    </div>

    <div class="position-relative mb-3">
        <input type="text" id="productSearch" class="form-control form-control-lg search-box shadow-sm" placeholder="Tìm tên hoặc vị trí..." autocomplete="off">
    </div>

    <div id="productContainer" class="d-flex flex-column gap-2">
        @forelse($products as $product)
            @php
                $pCatalog = $product->productCatalog;
                $pName = $pCatalog ? $pCatalog->product_name : 'N/A';
                $pLoc = $product->location ? $product->location->shelf_name : 'N/A';
                
                $pWholesale = $pCatalog && $pCatalog->wholesale_price ? number_format($pCatalog->wholesale_price) . ' đ' : '0 đ';
                $pAgency = $pCatalog && $pCatalog->agency_price ? number_format($pCatalog->agency_price) . ' đ' : '0 đ';
                $pRetail = $pCatalog && $pCatalog->retail_price ? number_format($pCatalog->retail_price) . ' đ' : '0 đ';
                
                $pId = $pCatalog ? $pCatalog->id : 0;
            @endphp

            <div class="card border-0 shadow-sm rounded-3 overflow-hidden product-card" 
                 data-search="{{ strtolower($pName . ' ' . $pLoc) }}">
                
                <a href="{{ $pId > 0 ? route('products.showCatalog', $pId) : '#' }}" 
                   class="card-body p-3 product-item d-flex justify-content-between align-items-center">
                    
                    <div style="flex: 1; min-width: 0;" class="pe-2">
                        <h6 class="fw-bold mb-1 text-truncate text-dark" style="font-size: 1rem;">
                            {{ $pName }}
                        </h6>
                        <div class="d-flex gap-1 mt-1">
                            <span class="mobile-location">📍 {{ $pLoc }}</span>
                            <span class="total-qty-badge">📦 Tồn: {{ $product->total_qty }}</span>
                        </div>
                    </div>
                    
                    <div class="text-end text-nowrap pe-1">
                        <div class="text-muted" style="font-size: 0.8rem; line-height: 1.1;">
                            <small>Sỉ:</small> <span class="fw-bold text-success">{{ $pWholesale }}</span>
                        </div>
                        <div class="text-primary fw-bold mt-1" style="font-size: 0.9rem; line-height: 1.1;">
                            <small class="text-muted fw-normal" style="font-size: 0.75rem;">Đ.Lý:</small> {{ $pAgency }}
                        </div>
                        <div class="mobile-price mt-1" style="font-size: 1.05rem; line-height: 1.1;">
                            <small class="text-muted fw-normal" style="font-size: 0.75rem;">Lẻ:</small> {{ $pRetail }}
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Chạm để xem</small>
                    </div>
                </a>
            </div>
        @empty
            <div class="text-center py-4 text-muted bg-white rounded-3 shadow-sm card border-0">
                📭 Không tìm thấy sản phẩm nào.
            </div>
        @endforelse
    </div>

    @if ($products->hasPages())
        <div class="d-flex justify-content-center my-3">
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm m-0 shadow-sm">
                    @if ($products->onFirstPage())
                        <li class="page-item disabled"><span class="page-link">« Trước</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $products->previousPageUrl() }}" rel="prev">« Trước</a></li>
                    @endif

                    @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                        <li class="page-item {{ $page == $products->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endforeach

                    @if ($products->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $products->nextPageUrl() }}" rel="next">Sau »</a></li>
                    @else
                        <li class="page-item disabled"><span class="page-link">Sau »</span></li>
                    @endif
                </ul>
            </nav>
        </div>
    @endif
</div>
@endsection

@push('scripts')
    @vite(['resources/js/products/lookup.js'])
@endpush