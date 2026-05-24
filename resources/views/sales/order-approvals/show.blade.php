@extends('layouts.admin')

@section('title', $order->order_code)

@section('content')
@php
    $orderTypeLabels = ['guest' => 'Guest', 'system' => 'System', 'manual' => 'Nội bộ'];
    $customerTypeLabels = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
@endphp

<div class="container-fluid px-1 px-md-2 mb-5" style="max-width: 1040px;">
    <a href="{{ route('sales.order_approvals.index') }}" class="btn btn-link px-0 mb-2"><i class="bi bi-arrow-left"></i> Xác nhận đơn hàng</a>

    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h3 class="fw-bold mb-1">{{ $order->order_code }}</h3>
                    <div class="text-muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                </div>
                <div class="d-flex gap-2 align-items-start">
                    <form method="POST" action="{{ route('sales.order_approvals.approve', $order) }}">
                        @csrf
                        <button class="btn btn-success" type="submit">Duyệt</button>
                    </form>
                </div>
            </div>

            <hr>

            <div class="row g-3">
                <div class="col-md-4"><div class="text-muted small">Người mua</div><div class="fw-semibold">{{ $order->buyer_name }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Số điện thoại</div><div class="fw-semibold">{{ $order->phone ?: '-' }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Nhóm khách</div><div class="fw-semibold">{{ $customerTypeLabels[$order->customer_type] ?? $order->customer_type }}</div></div>
                <div class="col-md-4"><div class="text-muted small">Loại đơn</div><div class="fw-semibold">{{ $orderTypeLabels[$order->order_type] ?? $order->order_type }}</div></div>
                <div class="col-md-8"><div class="text-muted small">Địa chỉ</div><div class="fw-semibold">{{ $order->address ?: '-' }}</div></div>
                <div class="col-12"><div class="text-muted small">Ghi chú</div><div>{{ $order->note ?: '-' }}</div></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-end">SL</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-end">Tổng</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product_name_snapshot ?: ($item->productCatalog?->product_name ?: 'N/A') }}</td>
                            <td class="text-end">{{ number_format($item->quantity) }}</td>
                            <td class="text-end">{{ number_format($item->unit_price) }} đ</td>
                            <td class="text-end fw-semibold">{{ number_format($item->total_amount) }} đ</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Tổng tiền</th>
                        <th class="text-end">{{ number_format($order->items->sum('total_amount')) }} đ</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('sales.order_approvals.reject', $order) }}" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body">
            <label class="form-label fw-semibold">Lý do từ chối</label>
            <textarea name="rejection_reason" class="form-control" rows="2">{{ old('rejection_reason') }}</textarea>
            <div class="text-end mt-3">
                <button class="btn btn-outline-danger" type="submit">Từ chối đơn</button>
            </div>
        </div>
    </form>
</div>
@endsection
