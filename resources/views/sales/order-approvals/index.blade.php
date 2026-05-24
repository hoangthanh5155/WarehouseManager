@extends('layouts.admin')

@section('title', 'Xác nhận đơn hàng')

@section('content')
@php
    $orderTypeLabels = ['guest' => 'Guest', 'system' => 'System', 'manual' => 'Nội bộ'];
    $customerTypeLabels = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
@endphp

<div class="container-fluid px-1 px-md-2 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
        <h3 class="fw-bold text-dark mb-0">Xác nhận đơn hàng</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã đơn</th>
                        <th>Người mua</th>
                        <th>Liên hệ</th>
                        <th>Loại</th>
                        <th>Nhóm khách</th>
                        <th class="text-end">SL</th>
                        <th class="text-end">Tổng tiền</th>
                        <th>Ngày đặt</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td class="fw-bold text-primary">{{ $order->order_code }}</td>
                            <td>
                                <div class="fw-semibold">{{ $order->buyer_name }}</div>
                                <div class="small text-muted">{{ $order->company_name ?: '-' }}</div>
                            </td>
                            <td>
                                <div>{{ $order->phone ?: '-' }}</div>
                                <div class="small text-muted">{{ $order->address ?: '-' }}</div>
                            </td>
                            <td>{{ $orderTypeLabels[$order->order_type] ?? $order->order_type }}</td>
                            <td>{{ $customerTypeLabels[$order->customer_type] ?? $order->customer_type }}</td>
                            <td class="text-end">{{ number_format($order->total_quantity ?? 0) }}</td>
                            <td class="text-end fw-semibold">{{ number_format($order->total_amount ?? 0) }} đ</td>
                            <td class="text-nowrap">{{ optional($order->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('sales.order_approvals.show', $order) }}" class="btn btn-sm btn-outline-primary">Xem</a>
                                    <form method="POST" action="{{ route('sales.order_approvals.approve', $order) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-success" type="submit">Duyệt</button>
                                    </form>
                                    <form method="POST" action="{{ route('sales.order_approvals.reject', $order) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Từ chối</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">Chưa có đơn hàng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white">{{ $orders->links() }}</div>
        @endif
    </div>
</div>
@endsection
