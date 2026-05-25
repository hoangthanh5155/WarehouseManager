@extends('layouts.admin')

@section('title', 'Chi tiết chuyến giao')

@section('content')
@php
    $statusClass = [
        'draft' => 'secondary',
        'picking' => 'info',
        'ready' => 'primary',
        'out_for_delivery' => 'warning',
        'completed' => 'success',
        'cancelled' => 'dark',
        'pending' => 'secondary',
        'ready_to_deliver' => 'primary',
        'in_delivery' => 'warning',
        'delivered' => 'success',
        'failed' => 'danger',
    ];
    $statusLabel = [
        'draft' => 'Nháp',
        'picking' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng',
        'out_for_delivery' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
        'pending' => 'Chờ xử lý',
        'ready_to_deliver' => 'Chờ giao',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
    ];
@endphp

<div class="container-fluid px-1 px-md-2" id="deliveryBatchShowPage">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="fw-bold text-dark mb-0">Chuyến {{ $batch->batch_code }}</h3>
        <a href="{{ route('delivery.batches.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Danh sách chuyến
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Thông tin chuyến</h5>
                    <div class="vstack gap-2">
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Mã chuyến</span>
                            <strong>{{ $batch->batch_code }}</strong>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Trạng thái</span>
                            <span class="badge text-bg-{{ $statusClass[$batch->status] ?? 'secondary' }}">{{ $statusLabel[$batch->status] ?? $batch->status }}</span>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">Ngày tạo</span>
                            <strong>{{ optional($batch->created_at)->format('d/m/Y H:i') }}</strong>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Ghi chú</div>
                            <div class="border rounded p-2 bg-light">{{ $batch->note ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Thêm đơn</h5>
                    <form class="delivery-api-form row g-2 align-items-end" method="POST" data-method="POST" data-endpoint="{{ route('delivery.batches.orders.store', $batch) }}" data-success-reload="true">
                        @csrf
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Đơn chờ giao</label>
                            <select name="fulfillment_order_id" class="form-select" required>
                                <option value="">Chọn đơn</option>
                                @foreach($availableOrders as $order)
                                    <option value="{{ $order->id }}">{{ $order->order_code }} - {{ $order->buyer_name ?: '-' }} (SL {{ $order->total_quantity ?? 0 }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-primary w-100 fw-semibold" type="submit">
                                <i class="bi bi-plus-lg me-1"></i>Thêm đơn
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Hàng trong chuyến</h5>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Tổng SN</th>
                                    <th>SN còn trong chuyến</th>
                                    <th>SN đã giao</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batch->serials->groupBy('product_catalog_id') as $catalogSerials)
                                    @php($catalog = $catalogSerials->first()->productCatalog)
                                    @php($openSerials = $catalogSerials->whereIn('status', ['reserved', 'assigned']))
                                    @php($deliveredSerials = $catalogSerials->where('status', 'delivered'))
                                    <tr>
                                        <td class="fw-semibold">{{ $catalog?->product_name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($catalogSerials->count()) }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse($openSerials as $serial)
                                                    <span class="badge text-bg-light border">{{ $serial->serial_number }}</span>
                                                @empty
                                                    <span class="text-muted small">-</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @forelse($deliveredSerials as $serial)
                                                    <span class="badge text-bg-success">{{ $serial->serial_number }}{{ $serial->fulfillmentOrder ? ' / ' . $serial->fulfillmentOrder->order_code : '' }}</span>
                                                @empty
                                                    <span class="text-muted small">-</span>
                                                @endforelse
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Chưa có SN trong chuyến.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="vstack gap-3">
                @forelse($batch->batchOrders as $batchOrder)
                    @php($order = $batchOrder->fulfillmentOrder)
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">{{ $order->order_code }}</h5>
                                    <div class="text-muted">{{ $order->buyer_name ?: '-' }}</div>
                                </div>
                                <span class="badge align-self-start text-bg-{{ $statusClass[$batchOrder->status] ?? 'secondary' }}">{{ $statusLabel[$batchOrder->status] ?? $batchOrder->status }}</span>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-end">SL</th>
                                            <th class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                                <td class="text-end fw-semibold">{{ number_format($item->total_amount) }} đ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('delivery.orders.print', $order) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-printer me-1"></i>In đơn
                                </a>
                                @if($order->public_token)
                                    <a href="{{ route('delivery.orders.public', $order->public_token) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>Phiếu điện tử
                                    </a>
                                @endif
                                <a href="{{ route('delivery.orders.index') }}" class="btn btn-success btn-sm fw-semibold">
                                    <i class="bi bi-truck me-1"></i>Xác nhận giao hàng
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center text-muted py-5">Chưa có đơn hàng.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/delivery-batches.js'])
@endpush
