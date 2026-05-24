@extends('layouts.shop')

@section('title', 'Đơn hàng')

@section('content')
@php
    $statusLabel = [
        'pending_approval' => 'Chờ duyệt',
        'pending' => 'Chờ xử lý',
        'pending_prepare' => 'Chờ soạn hàng',
        'ready_to_deliver' => 'Chờ giao',
        'rejected' => 'Từ chối',
        'reserved' => 'Đã giữ hàng',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'cancelled' => 'Đã hủy',
    ];
@endphp

<h2 class="fw-bold mb-4">Đơn hàng</h2>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Mã đơn</th><th>Trạng thái</th><th class="text-end">Tổng tiền</th><th>Ngày tạo</th><th></th></tr></thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="fw-bold">{{ $order->order_code }}</td>
                        <td><span class="badge text-bg-secondary">{{ $statusLabel[$order->status] ?? $order->status }}</span></td>
                        <td class="text-end">{{ number_format($order->total_amount ?? 0) }} đ</td>
                        <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('shop.account.orders.show', $order) }}">Xem</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Chưa có đơn hàng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())<div class="card-footer bg-white">{{ $orders->links() }}</div>@endif
</div>
@endsection
