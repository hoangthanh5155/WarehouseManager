@extends('layouts.shop')

@section('title', 'Đặt hàng thành công')

@section('content')
<div class="card border-0 shadow-sm mx-auto" style="max-width: 640px;">
    <div class="card-body text-center p-5">
        <div class="text-success fs-1 mb-3"><i class="bi bi-check-circle"></i></div>
        <h2 class="fw-bold">Đơn hàng đã được ghi nhận.</h2>
        <div class="alert alert-light border">Mã đơn: <strong>{{ $order->order_code }}</strong></div>
        <a href="{{ route('shop.index') }}" class="btn btn-primary">Tiếp tục mua hàng</a>
        @auth('customer')
            <a href="{{ route('shop.account.orders.show', $order) }}" class="btn btn-outline-primary">Xem đơn</a>
        @endauth
    </div>
</div>
@endsection
