<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu giao hàng {{ $order->order_code }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .print-sheet { box-shadow: none !important; border: 0 !important; }
        }
    </style>
</head>
<body class="bg-light">
@php
    $statusLabel = [
        'ready_to_deliver' => 'Chờ giao',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'cancelled' => 'Đã hủy',
    ];
    $serials = $order->preparedSerials;
@endphp
<div class="container py-4">
    <div class="d-flex justify-content-end gap-2 mb-3 no-print">
        @unless($publicView)
            <a href="{{ route('delivery.orders.index') }}" class="btn btn-outline-secondary">Quay lại</a>
        @endunless
        <button class="btn btn-primary" onclick="window.print()">In đơn</button>
    </div>

    <div class="card print-sheet border-0 shadow-sm">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="fw-bold mb-1">Phiếu giao hàng</h3>
                    <div class="text-muted">{{ $order->order_code }}</div>
                </div>
                <div class="text-md-end">
                    <div class="fw-semibold">{{ $statusLabel[$order->status] ?? $order->status }}</div>
                    <div class="text-muted small">Ngày tạo: {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                    @if($order->delivered_at)
                        <div class="text-muted small">Ngày giao: {{ optional($order->delivered_at)->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="text-muted small">Khách hàng</div>
                    <div class="fw-bold">{{ $order->buyer_name }}</div>
                    @if($order->company_name)
                        <div>{{ $order->company_name }}</div>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="text-muted small">Địa chỉ</div>
                    <div>{{ $order->address ?: '-' }}</div>
                    @if($order->tax_code)
                        <div class="text-muted small">SĐT: {{ $order->tax_code }}</div>
                    @endif
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-end">SL</th>
                            <th>Serial</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            @php($itemSerials = $serials->where('fulfillment_order_item_id', $item->id))
                            <tr>
                                <td class="fw-semibold">{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($itemSerials as $serial)
                                            <span class="badge text-bg-light border text-dark">{{ $serial->serial_number_snapshot }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($item->unit_price) }} đ</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_amount) }} đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Tổng tiền</th>
                            <th class="text-end">{{ number_format($order->items->sum('total_amount')) }} đ</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($order->note)
                <div class="mt-3">
                    <div class="text-muted small">Ghi chú</div>
                    <div>{{ $order->note }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
