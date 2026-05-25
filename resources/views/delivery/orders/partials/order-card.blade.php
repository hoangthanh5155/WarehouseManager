@php
    $modalId = 'deliverOrderModal' . $order->id;
    $activeBatchOrder = $order->batchOrders
        ->whereNotIn('status', ['delivered', 'failed', 'cancelled'])
        ->sortByDesc('id')
        ->first();
    $batch = $activeBatchOrder?->deliveryBatch;
@endphp
<div class="delivery-order-card">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div class="min-w-0">
            <div class="fw-bold text-break">{{ $order->order_code }}</div>
            <div class="text-muted small">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
        </div>
        <span class="badge text-bg-{{ $statusClass[$order->status] ?? 'secondary' }} flex-shrink-0">{{ $statusLabel[$order->status] ?? $order->status }}</span>
    </div>

    <div class="delivery-order-meta">
        <div>
            <span class="text-muted small d-block">Khách</span>
            <span class="fw-semibold text-break">{{ $order->buyer_name ?: '-' }}</span>
        </div>
        <div class="text-end">
            <span class="text-muted small d-block">Tổng tiền</span>
            <span class="fw-bold">{{ number_format($order->total_amount ?? 0) }} đ</span>
        </div>
    </div>

    <div class="mt-3">
        <div class="text-muted small mb-1">Sản phẩm cần giao</div>
        <div class="vstack gap-1">
            @foreach($order->items as $item)
                <div class="small">
                    <span class="fw-semibold">{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</span>
                    <span class="text-muted">x{{ number_format($item->quantity) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-2 small"><span class="text-muted">Chuyến giao:</span> <strong>{{ $batch?->batch_code ?: '-' }}</strong></div>
    <div class="mt-1 small"><span class="text-muted">Nhân viên giao:</span> <strong>{{ $batch?->deliveryUser?->displayName() ?: '-' }}</strong></div>
    <div class="mt-1 small"><span class="text-muted">Phương tiện:</span> <strong>{{ $batch?->vehicle?->displayName() ?: '-' }}</strong></div>

    <div class="d-grid gap-2 mt-3">
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
                <i class="bi bi-truck me-1"></i>Xác nhận giao
            </button>
            <form method="POST" action="{{ route('delivery.orders.confirm_fail', $order) }}" onsubmit="return confirm('Đánh dấu giao thất bại?')">
                @csrf
                <button class="btn btn-sm btn-outline-danger w-100" type="submit">
                    <i class="bi bi-x-circle me-1"></i>Thất bại
                </button>
            </form>
        @endif
    </div>
</div>
