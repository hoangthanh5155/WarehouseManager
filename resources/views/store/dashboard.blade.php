@extends('layouts.shop')

@section('title', 'Khu vực cửa hàng')

@section('content')
<div class="card border-0 shadow-sm shop-card">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <h2 class="fw-bold mb-1">Khu vực cửa hàng</h2>
            </div>
            <div class="text-md-end">
                <div class="fw-bold">{{ $customerUser->name }}</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ route('store.products.index') }}" class="btn btn-primary"><i class="bi bi-tags me-1"></i>Sản phẩm</a>
            <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary"><i class="bi bi-cart me-1"></i>Giỏ hàng</a>
            <a href="{{ route('shop.account.orders') }}" class="btn btn-outline-secondary">Lịch sử đơn</a>
        </div>
    </div>
</div>
@endsection
