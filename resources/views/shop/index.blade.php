@extends('layouts.shop')

@section('title', 'Sản phẩm')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Sản phẩm</h2>
        <div class="text-muted">{{ $pricingService->priceLabelFor($customerUser) }}</div>
    </div>
    <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary"><i class="bi bi-cart me-1"></i>Giỏ hàng</a>
</div>

<div class="row g-3">
    @forelse($productCatalogs as $catalog)
        @php($price = $pricingService->priceFor($catalog, $customerUser))
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="ratio ratio-4x3 bg-white border-bottom d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="bi bi-box-seam fs-1"></i>
                    </div>
                </div>
                <div class="card-body d-flex flex-column">
                    <h5 class="fw-bold">{{ $catalog->product_name }}</h5>
                    <div class="text-primary fw-bold fs-5 mb-1">{{ number_format($price) }} đ</div>
                    <div class="mb-3">
                        @if($catalog->stock_count > 0)
                            <span class="badge text-bg-success">Còn hàng</span>
                        @else
                            <span class="badge text-bg-secondary">Hết hàng</span>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('shop.cart.add') }}" class="mt-auto d-flex gap-2">
                        @csrf
                        <input type="hidden" name="product_catalog_id" value="{{ $catalog->id }}">
                        <input type="number" name="quantity" class="form-control" min="1" value="1" style="max-width: 92px;">
                        <button class="btn btn-primary flex-grow-1" {{ $catalog->stock_count <= 0 ? 'disabled' : '' }}>Thêm</button>
                    </form>
                    <a href="{{ route('shop.products.show', $catalog) }}" class="btn btn-link px-0 mt-2">Xem chi tiết</a>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="alert alert-light border">Chưa có sản phẩm.</div></div>
    @endforelse
</div>

<div class="mt-4">{{ $productCatalogs->links() }}</div>
@endsection
