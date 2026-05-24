@extends('layouts.shop')

@section('title', 'Khu vực cửa hàng')

@section('content')
<div class="card border-0 shadow-sm shop-card">
    <div class="card-body p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <div class="text-primary fw-bold small text-uppercase mb-1">Store portal</div>
                <h2 class="fw-bold mb-2">Khu vực cửa hàng</h2>
                <p class="text-muted mb-0">Tài khoản của bạn đã được duyệt giá đại lý. Đơn đặt vẫn vào hàng chờ fulfillment, kho sẽ xử lý SN và giao hàng sau.</p>
            </div>
            <div class="text-md-end">
                <div class="fw-bold">{{ $customerUser->name }}</div>
                <div class="text-muted small">{{ $customerUser->account_type }} / {{ $customerUser->customer_type }} / {{ $customerUser->approval_status }}</div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
            <a href="{{ route('store.products.index') }}" class="btn btn-primary"><i class="bi bi-tags me-1"></i>Xem giá đại lý</a>
            <a href="{{ route('shop.cart') }}" class="btn btn-outline-primary"><i class="bi bi-cart me-1"></i>Giỏ hàng</a>
            <a href="{{ route('shop.account.orders') }}" class="btn btn-outline-secondary">Lịch sử đơn</a>
        </div>
    </div>
</div>
@endsection
