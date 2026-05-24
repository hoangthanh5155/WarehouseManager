@extends('layouts.shop')

@section('title', 'Sản phẩm đại lý')

@section('content')
<div class="d-flex justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h2 class="fw-bold mb-0">Sản phẩm đại lý</h2>
        <div class="text-muted small">Giá đại lý được áp dụng cho tài khoản cửa hàng đã duyệt.</div>
    </div>
    <a href="{{ route('store.dashboard') }}" class="btn btn-outline-secondary btn-sm">Khu vực cửa hàng</a>
</div>

<div class="row g-2 g-md-3">
    @foreach($productCatalogs as $catalog)
        @php($price = $pricingService->priceFor($catalog, $customerUser))
        <div class="col-6 col-md-4 col-xl-3">
            <div class="card h-100 border-0 shadow-sm shop-card">
                <a href="{{ route('shop.products.show', $catalog) }}" class="shop-product-media text-decoration-none">
                    <i class="bi bi-box-seam fs-3"></i>
                </a>
                <div class="card-body d-flex flex-column">
                    <div class="shop-product-title fw-bold text-dark mb-1">{{ $catalog->product_name }}</div>
                    <div class="text-primary fw-bold shop-price mb-1">{{ number_format($price) }} đ</div>
                    <div class="mb-2"><span class="badge rounded-pill text-bg-primary">Giá đại lý</span></div>
                    <form method="POST" action="{{ route('shop.cart.add') }}" class="mt-auto d-flex gap-1 gap-sm-2">
                        @csrf
                        <input type="hidden" name="product_catalog_id" value="{{ $catalog->id }}">
                        <input type="number" name="quantity" class="form-control form-control-sm shop-qty" min="1" value="1">
                        <button class="btn btn-primary btn-sm flex-grow-1 fw-semibold shop-add-button" {{ $catalog->stock_count <= 0 ? 'disabled' : '' }}>Thêm</button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
<div class="mt-3">{{ $productCatalogs->links() }}</div>
@endsection
