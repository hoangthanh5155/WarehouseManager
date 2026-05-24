@extends('layouts.shop')

@section('title', $productCatalog->product_name)

@section('content')
@php($price = $pricingService->priceFor($productCatalog, $customerUser))
<div class="row g-3 g-lg-4">
    <div class="col-lg-5">
        <div class="shop-product-media rounded border" style="height: min(360px, 56vw);">
            <i class="bi bi-box-seam display-6"></i>
        </div>
    </div>
    <div class="col-lg-7">
        <a href="{{ route('shop.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Sản phẩm</a>
        <h2 class="fw-bold mb-1">{{ $productCatalog->product_name }}</h2>
        <div class="text-muted small mb-2">{{ $pricingService->priceLabelFor($customerUser) }}</div>
        <div class="text-primary fw-bold fs-2 mb-3">{{ number_format($price) }} đ</div>
        <div class="mb-3">
            @if($productCatalog->stock_count > 0)
                <span class="badge rounded-pill text-bg-success">Còn hàng</span>
            @else
                <span class="badge rounded-pill text-bg-secondary">Hết hàng</span>
            @endif
        </div>
        <form method="POST" action="{{ route('shop.cart.add') }}" class="d-flex gap-2" style="max-width: 330px;">
            @csrf
            <input type="hidden" name="product_catalog_id" value="{{ $productCatalog->id }}">
            <input type="number" name="quantity" class="form-control" min="1" value="1" aria-label="Số lượng">
            <button class="btn btn-primary fw-semibold" {{ $productCatalog->stock_count <= 0 ? 'disabled' : '' }}>Thêm vào giỏ</button>
        </form>
    </div>
</div>
@endsection
