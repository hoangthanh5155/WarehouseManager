@extends('layouts.shop')

@section('title', $order->order_code)

@section('content')
@php
    $statusLabel = [
        'pending' => 'Chờ xử lý',
        'reserved' => 'Đã giữ hàng',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'cancelled' => 'Đã hủy',
    ];
@endphp

<a href="{{ route('shop.account.orders') }}" class="btn btn-link px-0"><i class="bi bi-arrow-left"></i> Đơn hàng</a>
<h2 class="fw-bold mb-3">{{ $order->order_code }}</h2>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="text-muted">Trạng thái</div><strong>{{ $statusLabel[$order->status] ?? $order->status }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Người mua</div><strong>{{ $order->buyer_name }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Ngày tạo</div><strong>{{ optional($order->created_at)->format('d/m/Y H:i') }}</strong></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Sản phẩm</th><th class="text-end">SL</th><th class="text-end">Đơn giá</th><th class="text-end">Tổng</th></tr></thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot }}</td>
                            <td class="text-end">{{ number_format($item->quantity) }}</td>
                            <td class="text-end">{{ number_format($item->unit_price) }} đ</td>
                            <td class="text-end fw-bold">{{ number_format($item->total_amount) }} đ</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
