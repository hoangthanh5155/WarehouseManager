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
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Loại</th>
                            <th>Khách</th>
                            <th>Trạng thái</th>
                            <th>Sản phẩm cần giao</th>
                            <th>Chuyến giao</th>
                            <th class="text-end">Tổng tiền</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @include('delivery.orders.partials.order-row', ['order' => $order])
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Chưa có đơn hàng.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="delivery-order-mobile-list d-md-none">
                @forelse($orders as $order)
                    @include('delivery.orders.partials.order-card', ['order' => $order])
                @empty
                    <div class="text-center text-muted py-4">Chưa có đơn hàng.</div>
                @endforelse
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer bg-white">{{ $orders->links() }}</div>
        @endif
    </div>
</div>

@foreach($orders as $order)
    @php
        $modalId = 'deliverOrderModal' . $order->id;
        $activeBatchOrder = $order->batchOrders
            ->whereNotIn('status', ['delivered', 'failed', 'cancelled'])
            ->sortByDesc('id')
            ->first();
        $batch = $activeBatchOrder?->deliveryBatch;
        $batchSerials = $batch?->serials
            ->whereIn('status', ['reserved', 'assigned'])
            ->values() ?? collect();
        $orderItems = $order->items->map(fn ($item) => [
            'id' => $item->id,
            'product_catalog_id' => $item->product_catalog_id,
            'product_name' => $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A'),
            'quantity' => (int) $item->quantity,
        ])->values();
        $batchSerialPayload = $batchSerials->map(fn ($serial) => [
            'serial_number' => $serial->serial_number,
            'product_catalog_id' => $serial->product_catalog_id,
            'status' => $serial->status,
            'fulfillment_order_id' => $serial->fulfillment_order_id,
        ])->values();
    @endphp
    <div class="modal fade delivery-confirm-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true"
        data-order-items='@json($orderItems)'
        data-batch-serials='@json($batchSerialPayload)'>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ route('delivery.orders.confirm_deliver', $order) }}">
                    @csrf
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title fw-bold">Xác nhận giao hàng</h5>
                            <div class="text-muted small">{{ $order->order_code }} - {{ $order->buyer_name ?: 'Khách chưa đặt tên' }}</div>
                            <div class="text-muted small">Chuyến: {{ $batch?->batch_code ?: 'Chưa có chuyến' }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @unless($batch)
                            <div class="alert alert-warning">Đơn chưa nằm trong chuyến giao, chưa thể xác nhận.</div>
                        @endunless

                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sản phẩm cần giao</th>
                                        <th class="text-end">Cần</th>
                                        <th class="text-end">Đã pass</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr data-progress-row data-catalog-id="{{ $item->product_catalog_id }}">
                                            <td>{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                                            <td class="text-end">{{ number_format($item->quantity) }}</td>
                                            <td class="text-end fw-bold" data-progress-text>0/{{ (int) $item->quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <label class="form-label fw-semibold">Quét SN từ chuyến</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                            <input type="text" class="form-control form-control-lg" data-scan-input placeholder="Quét SN" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                        </div>
                        <input type="hidden" name="serials" data-serial-lines>
                        <div class="small mt-2" data-scan-message></div>

                        <div class="mt-3">
                            <div class="fw-semibold mb-1">SN đã quét</div>
                            <div class="d-flex flex-wrap gap-1" data-scanned-list>
                                <span class="text-muted small">Chưa có SN.</span>
                            </div>
                        </div>

                        <label class="form-label fw-semibold mt-3">Ghi chú</label>
                        <textarea name="note" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-success fw-bold" data-submit-delivery disabled>Giao thành công</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .delivery-order-mobile-list { padding: 12px; }
        .delivery-order-card { border-bottom: 1px solid #e5e7eb; padding: 14px 0; }
        .delivery-order-card:first-child { padding-top: 0; }
        .delivery-order-card:last-child { border-bottom: 0; padding-bottom: 0; }
        .delivery-order-meta {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 12px;
            align-items: start;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.delivery-confirm-modal').forEach((modal) => {
    const items = JSON.parse(modal.dataset.orderItems || '[]');
    const batchSerials = JSON.parse(modal.dataset.batchSerials || '[]');
    const serialMap = new Map(batchSerials.map((row) => [String(row.serial_number), row]));
    const scanned = [];
    const input = modal.querySelector('[data-scan-input]');
    const serialLines = modal.querySelector('[data-serial-lines]');
    const list = modal.querySelector('[data-scanned-list]');
    const message = modal.querySelector('[data-scan-message]');
    const submit = modal.querySelector('[data-submit-delivery]');

    function setMessage(text, type = 'muted') {
        message.textContent = text;
        message.className = `small mt-2 text-${type}`;
    }

    function countsByCatalog() {
        return scanned.reduce((map, serial) => {
            const row = serialMap.get(serial);
            const key = String(row.product_catalog_id);
            map.set(key, (map.get(key) || 0) + 1);
            return map;
        }, new Map());
    }

    function render() {
        const counts = countsByCatalog();
        let complete = items.length > 0;

        modal.querySelectorAll('[data-progress-row]').forEach((row) => {
            const catalogId = String(row.dataset.catalogId);
            const item = items.find((candidate) => String(candidate.product_catalog_id) === catalogId);
            const passed = counts.get(catalogId) || 0;
            row.querySelector('[data-progress-text]').textContent = `${passed}/${item.quantity}`;
            if (passed !== Number(item.quantity)) complete = false;
        });

        serialLines.value = scanned.join('\n');
        list.innerHTML = scanned.length
            ? scanned.map((serial) => `<span class="badge text-bg-light border">${serial}</span>`).join('')
            : '<span class="text-muted small">Chưa có SN.</span>';
        submit.disabled = !complete;
    }

    function addSerial(raw) {
        const serial = String(raw || '').trim();
        if (!serial) return;
        if (scanned.includes(serial)) {
            setMessage('SN đã quét trong đơn này.', 'warning');
            return;
        }
        const batchSerial = serialMap.get(serial);
        if (!batchSerial) {
            setMessage('SN không thuộc chuyến giao.', 'danger');
            return;
        }
        if (batchSerial.fulfillment_order_id) {
            setMessage('SN đã giao hoặc đã gắn cho đơn khác.', 'danger');
            return;
        }
        const item = items.find((candidate) => Number(candidate.product_catalog_id) === Number(batchSerial.product_catalog_id));
        if (!item) {
            setMessage('SN sai sản phẩm.', 'danger');
            return;
        }
        const count = scanned.filter((value) => Number(serialMap.get(value).product_catalog_id) === Number(item.product_catalog_id)).length;
        if (count >= Number(item.quantity)) {
            setMessage('Sản phẩm này đã đủ SN.', 'warning');
            return;
        }
        scanned.push(serial);
        setMessage('SN hợp lệ.', 'success');
        render();
    }

    function processInput() {
        addSerial(input.value);
        input.value = '';
        input.focus();
    }

    input?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === 'Tab') {
            event.preventDefault();
            processInput();
        }
    });

    modal.querySelector('form')?.addEventListener('submit', (event) => {
        if (document.activeElement === input) {
            event.preventDefault();
            processInput();
        }
    });

    render();
});
</script>
@endpush
