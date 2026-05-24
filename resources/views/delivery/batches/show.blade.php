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
        'delivered' => 'success',
        'failed' => 'danger',
        'assigned' => 'primary',
        'reserved' => 'info',
        'released' => 'dark',
    ];
    $statusLabel = [
        'draft' => 'Nháp',
        'picking' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng',
        'out_for_delivery' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
        'pending' => 'Chờ xử lý',
        'reserved' => 'Đã giữ hàng',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'assigned' => 'Đã gán',
        'released' => 'Đã thả',
    ];
    $customerLabel = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
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
                            <div class="border rounded p-2 bg-light">{{ $batch->note ?: 'Không có ghi chú' }}</div>
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
                            <label class="form-label fw-semibold">Đơn cần giao</label>
                            <select name="fulfillment_order_id" class="form-select" required>
                                <option value="">Chọn đơn</option>
                                @foreach($availableOrders as $order)
                                    <option value="{{ $order->id }}">{{ $order->order_code }} - {{ $order->buyer_name }} (SL {{ $order->total_quantity ?? 0 }})</option>
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

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Quét SN</h5>
                    <form class="delivery-api-form mb-3" method="POST" data-method="POST" data-endpoint="{{ route('delivery.batches.serials.reserve', $batch) }}" data-success-reload="true" data-serial-list-form="serials">
                        @csrf
                        <label class="form-label fw-semibold">Serial</label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="serials[]" placeholder="Quét hoặc nhập SN">
                            <button class="btn btn-primary fw-semibold" type="submit">
                                <i class="bi bi-upc-scan me-1"></i>Giữ SN
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-sm align-middle">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Serial</th>
                                    <th>Sản phẩm</th>
                                    <th>Trạng thái</th>
                                    <th>Đơn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batch->serials->sortByDesc('created_at') as $serial)
                                    <tr>
                                        <td class="fw-semibold">{{ $serial->serial_number }}</td>
                                        <td>{{ $serial->productCatalog->product_name ?? 'N/A' }}</td>
                                        <td><span class="badge text-bg-{{ $statusClass[$serial->status] ?? 'secondary' }}">{{ $statusLabel[$serial->status] ?? $serial->status }}</span></td>
                                        <td>{{ $serial->deliveryBatchOrder?->fulfillmentOrder?->order_code ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Chưa có dữ liệu.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="vstack gap-3">
                @forelse($batch->batchOrders as $batchOrder)
                    @php($order = $batchOrder->fulfillmentOrder)
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                <div>
                                    <h5 class="fw-bold mb-1">{{ $order->order_code }}</h5>
                                    <div class="text-muted">{{ $order->buyer_name }} · {{ $customerLabel[$order->customer_type] ?? $order->customer_type }}</div>
                                </div>
                                <span class="badge align-self-start text-bg-{{ $statusClass[$batchOrder->status] ?? 'secondary' }}">{{ $statusLabel[$batchOrder->status] ?? $batchOrder->status }}</span>
                            </div>

                            <div class="table-responsive mb-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-end">SL</th>
                                            <th class="text-end">Đơn giá</th>
                                            <th class="text-end">Thành tiền</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                            <tr>
                                                <td>{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                                <td class="text-end">{{ number_format($item->unit_price) }} đ</td>
                                                <td class="text-end fw-semibold">{{ number_format($item->total_amount) }} đ</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <form class="delivery-api-form mb-3" method="POST" data-method="POST" data-endpoint="{{ route('delivery.orders.serials.assign', $batchOrder) }}" data-success-reload="true" data-serial-lines-form="serials">
                                @csrf
                                <label class="form-label fw-semibold">SN xác minh</label>
                                <textarea class="form-control" name="serials" rows="2" placeholder="Mỗi dòng một SN"></textarea>
                                <button class="btn btn-outline-primary btn-sm mt-2" type="submit">
                                    <i class="bi bi-check2-square me-1"></i>Xác minh SN
                                </button>
                            </form>

                            <div class="d-flex flex-wrap gap-2">
                                <form class="delivery-api-form" method="POST" data-method="POST" data-endpoint="{{ route('delivery.orders.deliver', $batchOrder) }}" data-success-reload="true" data-confirm="Xác nhận giao thành công?">
                                    @csrf
                                    <button class="btn btn-success btn-sm fw-semibold" type="submit">
                                        <i class="bi bi-truck me-1"></i>Giao thành công
                                    </button>
                                </form>
                                <form class="delivery-api-form" method="POST" data-method="POST" data-endpoint="{{ route('delivery.orders.fail', $batchOrder) }}" data-success-reload="true" data-confirm="Đánh dấu giao thất bại?">
                                    @csrf
                                    <input type="hidden" name="note" value="Giao thất bại">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">
                                        <i class="bi bi-x-circle me-1"></i>Giao thất bại
                                    </button>
                                </form>
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
