@php
    $modalId = 'deliverOrderModal' . $order->id;
    $activeBatchOrder = $order->batchOrders
        ->whereNotIn('status', ['delivered', 'failed', 'cancelled'])
        ->sortByDesc('id')
        ->first();
    $batch = $activeBatchOrder?->deliveryBatch;
@endphp
<tr>
    <td>
        <div class="fw-bold">{{ $order->order_code }}</div>
        <div class="text-muted small">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
    </td>
    <td>{{ $typeLabel[$order->order_type] ?? $order->order_type }}</td>
    <td>
        <div class="fw-semibold">{{ $order->buyer_name ?: '-' }}</div>
        <div class="text-muted small">{{ $customerLabel[$order->customer_type] ?? $order->customer_type }}</div>
    </td>
    <td><span class="badge text-bg-{{ $statusClass[$order->status] ?? 'secondary' }}">{{ $statusLabel[$order->status] ?? $order->status }}</span></td>
    <td>
        <div class="vstack gap-1">
            @foreach($order->items as $item)
                <div class="small">
                    <span class="fw-semibold">{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</span>
                    <span class="text-muted">x{{ number_format($item->quantity) }}</span>
                </div>
            @endforeach
        </div>
    </td>
    <td>{{ $batch?->batch_code ?: '-' }}</td>
    <td>{{ $batch?->deliveryUser?->displayName() ?: '-' }}</td>
    <td>{{ $batch?->vehicle?->displayName() ?: '-' }}</td>
    <td class="text-end">{{ number_format($order->total_amount ?? 0) }} đ</td>
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
                    <i class="bi bi-truck me-1"></i>Xác nhận giao
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
