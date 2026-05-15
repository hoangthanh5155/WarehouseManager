@extends('layouts.admin')

@section('content')
<style>
    .lookup-shell {
        max-width: 1320px;
        margin: 0 auto;
    }

    .lookup-header {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        padding: 18px;
    }

    .lookup-title {
        letter-spacing: 0.02em;
    }

    .lookup-search-wrap {
        position: relative;
    }

    .lookup-search-icon {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
        pointer-events: none;
    }

    .search-box {
        border: 1px solid #dbe3ef;
        border-radius: 14px;
        padding: 13px 16px 13px 44px;
        font-weight: 600;
        box-shadow: none;
    }

    .search-box:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.12);
    }

    .supplier-chip-row,
    .sort-chip-row {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding-bottom: 2px;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    .supplier-chip-row::-webkit-scrollbar,
    .sort-chip-row::-webkit-scrollbar {
        display: none;
    }

    .supplier-chip,
    .sort-chip {
        border-radius: 999px;
        white-space: nowrap;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.86rem;
        transition: all 0.15s ease;
    }

    .supplier-chip {
        color: #334155;
        background: #f8fafc;
        border: 1px solid #dbe3ef;
        padding: 8px 13px;
    }

    .supplier-chip:hover,
    .supplier-chip.active {
        color: #ffffff;
        background: #0d6efd;
        border-color: #0d6efd;
    }

    .sort-chip {
        color: #475569;
        background: #eef2f7;
        border: 1px solid transparent;
        padding: 7px 12px;
    }

    .sort-chip:hover,
    .sort-chip.active {
        color: #0f172a;
        background: #ffffff;
        border-color: #cbd5e1;
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    .product-card {
        border: 0;
        border-radius: 16px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.07);
        transition: transform 0.16s ease, box-shadow 0.16s ease;
        min-width: 0;
    }

    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.11);
    }

    .product-thumb {
        min-height: 112px;
        background: linear-gradient(135deg, #eef6ff 0%, #f8fafc 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid #eef2f7;
    }

    .product-thumb-inner {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: #ffffff;
        color: #2563eb;
        box-shadow: inset 0 0 0 1px #dbeafe;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
    }

    .product-name {
        color: #0f172a;
        font-size: 0.98rem;
        line-height: 1.3;
        min-height: 2.55rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        max-width: 100%;
        padding: 5px 8px;
        border-radius: 9px;
        background: #f8fafc;
        color: #475569;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .meta-pill span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .stock-badge {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #bbf7d0;
        border-radius: 10px;
        padding: 6px 8px;
        font-weight: 800;
        font-size: 0.82rem;
    }

    .price-panel {
        background: #f8fafc;
        border: 1px solid #eef2f7;
        border-radius: 12px;
        padding: 10px;
    }

    .price-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        font-size: 0.8rem;
    }

    .price-row + .price-row {
        margin-top: 6px;
    }

    .price-label {
        color: #64748b;
        white-space: nowrap;
    }

    .price-value {
        color: #0f172a;
        font-weight: 800;
        text-align: right;
        overflow-wrap: anywhere;
    }

    .price-value.retail {
        color: #dc2626;
    }

    .detail-link {
        border-radius: 11px;
        font-weight: 800;
    }

    @media (min-width: 768px) {
        .lookup-header {
            padding: 22px;
        }

        .product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }
    }

    @media (min-width: 1200px) {
        .product-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    @media (max-width: 420px) {
        .lookup-shell {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .lookup-header {
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 10px !important;
        }

        .lookup-header .gap-3 {
            gap: 8px !important;
        }

        .lookup-title {
            font-size: 1.2rem;
        }

        .lookup-header .small {
            font-size: 0.72rem;
        }

        .lookup-header .btn {
            padding: 6px 10px !important;
            font-size: 0.82rem;
            border-radius: 10px;
        }

        .lookup-search-wrap {
            margin-bottom: 10px !important;
        }

        .lookup-search-icon {
            left: 12px;
            font-size: 0.9rem;
        }

        .search-box {
            min-height: 42px;
            padding: 8px 12px 8px 36px;
            border-radius: 12px;
            font-size: 0.92rem;
        }

        .supplier-chip-row,
        .sort-chip-row {
            gap: 6px;
        }

        .supplier-chip-row {
            margin-bottom: 8px !important;
        }

        .supplier-chip,
        .sort-chip {
            padding: 6px 9px;
            font-size: 0.76rem;
        }

        .product-grid {
            gap: 10px;
        }

        .product-card {
            border-radius: 14px;
        }

        .product-thumb {
            min-height: 78px;
        }

        .product-thumb-inner {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            font-size: 1.25rem;
        }

        .product-card .card-body {
            padding: 10px !important;
        }

        .product-name {
            font-size: 0.86rem;
            min-height: 2.25rem;
        }

        .stock-badge {
            padding: 4px 6px;
            border-radius: 8px;
            font-size: 0.72rem;
        }

        .meta-pill {
            padding: 4px 6px;
            border-radius: 8px;
            font-size: 0.7rem;
        }

        .price-panel {
            padding: 8px;
            border-radius: 10px;
            margin-bottom: 10px !important;
        }

        .price-row {
            align-items: flex-start;
            flex-direction: column;
            gap: 1px;
            font-size: 0.72rem;
        }

        .price-value {
            text-align: left;
        }

        .detail-link {
            padding: 6px 8px;
            font-size: 0.78rem;
            border-radius: 9px;
        }
    }
</style>

@php
    $activeSupplierId = (string) ($supplierId ?? request('supplier_id', ''));
    $activeSort = $sort ?? request('sort', 'featured');
    $supplierBaseQuery = $activeSort !== 'featured' ? ['sort' => $activeSort] : [];
    $sortOptions = [
        'featured' => 'Nổi bật',
        'newest' => 'Mới nhập',
        'stock_desc' => 'Tồn nhiều',
        'stock_asc' => 'Tồn ít',
        'price_asc' => 'Giá thấp',
        'price_desc' => 'Giá cao',
    ];
@endphp

<div class="lookup-shell container-fluid px-1 px-md-2 mb-5">
    <div class="lookup-header mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <div class="text-uppercase text-primary fw-bold small mb-1">Quản lý sản phẩm</div>
                <h3 class="lookup-title fw-bold text-dark m-0">TRA CỨU HÀNG HÓA</h3>
            </div>

            <a href="{{ route('products.import') }}" class="btn btn-primary fw-bold px-3 py-2 align-self-start align-self-md-center">
                <i class="bi bi-box-arrow-in-down me-1"></i> Nhập mới
            </a>
        </div>

        <div class="lookup-search-wrap mb-3">
            <i class="bi bi-search lookup-search-icon"></i>
            <input type="text" id="productSearch" class="form-control form-control-lg search-box" placeholder="Tìm hàng, NCC, vị trí..." autocomplete="off">
        </div>

        <div class="supplier-chip-row mb-3" aria-label="Lọc theo nhà cung cấp">
            <a href="{{ route('products.index', $supplierBaseQuery) }}" class="supplier-chip {{ $activeSupplierId === '' ? 'active' : '' }}">
                Tất cả
            </a>
            @foreach($suppliers as $supplier)
                <a href="{{ route('products.index', array_merge($supplierBaseQuery, ['supplier_id' => $supplier->id])) }}" class="supplier-chip {{ $activeSupplierId === (string) $supplier->id ? 'active' : '' }}">
                    {{ $supplier->name }}
                </a>
            @endforeach
        </div>

        <div class="sort-chip-row" aria-label="Sắp xếp">
            @foreach($sortOptions as $sortValue => $sortLabel)
                @php
                    $sortQuery = ['sort' => $sortValue];
                    if ($activeSupplierId !== '') {
                        $sortQuery['supplier_id'] = $activeSupplierId;
                    }
                @endphp
                <a href="{{ route('products.index', $sortQuery) }}" class="sort-chip {{ $activeSort === $sortValue ? 'active' : '' }}">
                    {{ $sortLabel }}
                </a>
            @endforeach
        </div>
    </div>

    <div id="productContainer" class="product-grid">
        @forelse($products as $product)
            @php
                $pCatalog = $product->productCatalog;
                $pSupplier = $product->supplier;
                $pLocation = $product->location;

                $pName = $pCatalog ? $pCatalog->product_name : 'N/A';
                $pSupplierName = $pSupplier ? $pSupplier->name : 'N/A';
                $pLoc = $pLocation ? $pLocation->shelf_name : 'N/A';
                $pWholesale = $pCatalog && $pCatalog->wholesale_price ? number_format($pCatalog->wholesale_price) . ' đ' : '0 đ';
                $pAgency = $pCatalog && $pCatalog->agency_price ? number_format($pCatalog->agency_price) . ' đ' : '0 đ';
                $pRetail = $pCatalog && $pCatalog->retail_price ? number_format($pCatalog->retail_price) . ' đ' : '0 đ';
                $pId = $pCatalog ? $pCatalog->id : 0;
                $detailUrl = $pId > 0 ? route('products.showCatalog', $pId) : '#';
            @endphp

            <article class="product-card"
                     data-search="{{ mb_strtolower($pName . ' ' . $pSupplierName . ' ' . $pLoc) }}">
                <div class="product-thumb">
                    <div class="product-thumb-inner">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h6 class="product-name fw-bold m-0">{{ $pName }}</h6>
                        <span class="stock-badge text-nowrap">{{ $product->total_qty }} tồn</span>
                    </div>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <div class="meta-pill">
                            <i class="bi bi-building"></i>
                            <span>{{ $pSupplierName }}</span>
                        </div>
                        <div class="meta-pill">
                            <i class="bi bi-geo-alt"></i>
                            <span>{{ $pLoc }}</span>
                        </div>
                    </div>

                    <div class="price-panel mb-3">
                        <div class="price-row">
                            <span class="price-label">Giá nhập</span>
                            <span class="price-value">{{ $pWholesale }}</span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Đại lý</span>
                            <span class="price-value">{{ $pAgency }}</span>
                        </div>
                        <div class="price-row">
                            <span class="price-label">Bán lẻ</span>
                            <span class="price-value retail">{{ $pRetail }}</span>
                        </div>
                    </div>

                    <a href="{{ $detailUrl }}" class="btn btn-outline-primary w-100 detail-link">
                        Xem chi tiết
                    </a>
                </div>
            </article>
        @empty
            <div class="grid-empty text-center py-5 text-muted bg-white rounded-4 shadow-sm border-0" style="grid-column: 1 / -1;">
                <i class="bi bi-inboxes fs-1 d-block mb-2 text-secondary"></i>
                Không tìm thấy hàng hóa đang tồn kho.
            </div>
        @endforelse
    </div>

    <div id="clientSearchEmpty" class="d-none text-center py-5 text-muted bg-white rounded-4 shadow-sm border-0 mt-3">
        <i class="bi bi-search fs-1 d-block mb-2 text-secondary"></i>
        Không có sản phẩm phù hợp với từ khóa trên trang hiện tại.
    </div>

    @if ($products->hasPages())
        <div class="d-flex justify-content-center my-4">
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
