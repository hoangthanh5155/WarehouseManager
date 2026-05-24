@extends('layouts.admin')

@section('title', 'Đơn cần giao')

@section('content')
@php
    $statusClass = [
        'pending' => 'secondary',
        'reserved' => 'info',
        'in_delivery' => 'primary',
        'delivered' => 'success',
        'failed' => 'danger',
        'cancelled' => 'dark',
    ];
    $statusLabel = [
        'pending' => 'Chờ xử lý',
        'reserved' => 'Đã giữ hàng',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'cancelled' => 'Đã hủy',
    ];
    $typeLabel = ['manual' => 'Thủ công', 'system' => 'Hệ thống', 'guest' => 'Khách lẻ'];
    $customerLabel = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
@endphp

<div class="container-fluid px-1 px-md-2">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="fw-bold text-dark mb-0">Đơn cần giao</h3>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Loại</th>
                            <th>Người mua</th>
                            <th>Nhóm khách</th>
                            <th>Trạng thái</th>
                            <th class="text-end">SL</th>
                            <th class="text-end">Tổng tiền</th>
                            <th>Ngày tạo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td class="fw-bold">{{ $order->order_code }}</td>
                                <td>{{ $typeLabel[$order->order_type] ?? $order->order_type }}</td>
                                <td>{{ $order->buyer_name }}</td>
                                <td>{{ $customerLabel[$order->customer_type] ?? $order->customer_type }}</td>
                                <td><span class="badge text-bg-{{ $statusClass[$order->status] ?? 'secondary' }}">{{ $statusLabel[$order->status] ?? $order->status }}</span></td>
                                <td class="text-end">{{ number_format($order->total_quantity ?? 0) }}</td>
                                <td class="text-end">{{ number_format($order->total_amount ?? 0) }} đ</td>
                                <td>{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Chưa có đơn hàng.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
