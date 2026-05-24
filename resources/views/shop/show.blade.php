@extends('layouts.shop')

@section('title', $productCatalog->product_name)

@section('content')
@php($price = $pricingService->priceFor($productCatalog, $customerUser))
<div class="row g-4">
    <div class="col-lg-5">
        <div class="ratio ratio-1x1 bg-white border rounded d-flex align-items-center justify-content-center">
            <div class="text-center text-muted"><i class="bi bi-box-seam fs-1"></i></div>
        </div>
    </div>
    <div class="col-lg-7">
        <a href="{{ route('shop.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Sản phẩm</a>
        <h2 class="fw-bold">{{ $productCatalog->product_name }}</h2>
        <div class="text-muted mb-2">{{ $pricingService->priceLabelFor($customerUser) }}</div>
        <div class="text-primary fw-bold display-6 mb-3">{{ number_format($price) }} đ</div>
        <div class="mb-4">
            @if($productCatalog->stock_count > 0)
                <span class="badge text-bg-success">Còn hàng</span>
            @else
                <span class="badge text-bg-secondary">Hết hàng</span>
            @endif
        </div>
        <form method="POST" action="{{ route('shop.cart.add') }}" class="d-flex gap-2" style="max-width: 360px;">
            @csrf
            <input type="hidden" name="product_catalog_id" value="{{ $productCatalog->id }}">
            <input type="number" name="quantity" class="form-control" min="1" value="1">
            <button class="btn btn-primary" {{ $productCatalog->stock_count <= 0 ? 'disabled' : '' }}>Thêm vào giỏ</button>
        </form>
    </div>
</div>
@endsection
