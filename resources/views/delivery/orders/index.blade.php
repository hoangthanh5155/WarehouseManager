@extends('layouts.admin')

@section('title', 'Đơn cần giao')

@section('content')
@php
    $statusClass = [
        'ready_to_deliver' => 'primary',
        'in_delivery' => 'warning',
        'delivered' => 'success',
        'failed' => 'danger',
        'cancelled' => 'dark',
    ];
    $statusLabel = [
        'ready_to_deliver' => 'Chờ giao',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'cancelled' => 'Đã hủy',
    ];
    $typeLabel = ['manual' => 'Xuất thường', 'system' => 'Hệ thống', 'guest' => 'Khách lẻ'];
    $customerLabel = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
@endphp

<div class="container-fluid px-1 px-md-2">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="fw-bold text-dark mb-0">Đơn cần giao</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Loại</th>
                            <th>Người mua</th>
                            <th>Trạng thái</th>
                            <th class="text-end">SL</th>
                            <th class="text-end">Tổng tiền</th>
                            <th>SN giữ</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $modalId = 'deliverOrderModal' . $order->id;
                                $preparedSerials = $order->preparedSerials->where('status', 'prepared');
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold">{{ $order->order_code }}</div>
                                    <div class="text-muted small">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                                </td>
                                <td>{{ $typeLabel[$order->order_type] ?? $order->order_type }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $order->buyer_name }}</div>
                                    <div class="text-muted small">{{ $customerLabel[$order->customer_type] ?? $order->customer_type }}</div>
                                </td>
                                <td><span class="badge text-bg-{{ $statusClass[$order->status] ?? 'secondary' }}">{{ $statusLabel[$order->status] ?? $order->status }}</span></td>
                                <td class="text-end">{{ number_format($order->total_quantity ?? 0) }}</td>
                                <td class="text-end">{{ number_format($order->total_amount ?? 0) }} đ</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1" style="max-width: 260px;">
                                        @forelse($preparedSerials->take(6) as $serial)
                                            <span class="badge text-bg-light border">{{ $serial->serial_number_snapshot }}</span>
                                        @empty
                                            <span class="text-muted small">-</span>
                                        @endforelse
                                        @if($preparedSerials->count() > 6)
                                            <span class="badge text-bg-secondary">+{{ $preparedSerials->count() - 6 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-1">
                                        <a href="{{ route('delivery.orders.print', $order) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-printer me-1"></i>In đơn
                                        </a>
                                        @if($order->public_token)
                                            <a href="{{ route('delivery.orders.public', $order->public_token) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>Phiếu điện tử
                                            </a>
                                        @endif
                                        @if(in_array($order->status, ['ready_to_deliver', 'in_delivery'], true))
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                                <i class="bi bi-truck me-1"></i>Giao hàng
                                            </button>
                                            <form method="POST" action="{{ route('delivery.orders.confirm_fail', $order) }}" onsubmit="return confirm('Đánh dấu giao thất bại?')">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="bi bi-x-circle me-1"></i>Thất bại
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="{{ route('delivery.orders.confirm_deliver', $order) }}">
                                            @csrf
                                            <div class="modal-header bg-light">
                                                <div>
                                                    <h5 class="modal-title fw-bold">Xác nhận giao hàng</h5>
                                                    <div class="text-muted small">{{ $order->order_code }} - {{ $order->buyer_name }}</div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="table-responsive mb-3">
                                                    <table class="table table-sm align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>Sản phẩm</th>
                                                                <th class="text-end">SL</th>
                                                                <th>SN đã soạn</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($order->items as $item)
                                                                @php($itemSerials = $preparedSerials->where('fulfillment_order_item_id', $item->id))
                                                                <tr>
                                                                    <td>{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                                                                    <td class="text-end">{{ number_format($item->quantity) }}</td>
                                                                    <td>
                                                                        <div class="d-flex flex-wrap gap-1">
                                                                            @foreach($itemSerials as $serial)
                                                                                <span class="badge text-bg-light border">{{ $serial->serial_number_snapshot }}</span>
                                                                            @endforeach
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <label class="form-label fw-semibold">SN xác nhận</label>
                                                <textarea name="serials" rows="5" class="form-control" required>{{ $preparedSerials->pluck('serial_number_snapshot')->implode("\n") }}</textarea>
                                                <label class="form-label fw-semibold mt-3">Ghi chú</label>
                                                <textarea name="note" rows="2" class="form-control"></textarea>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                <button type="submit" class="btn btn-success fw-bold">Giao thành công</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
