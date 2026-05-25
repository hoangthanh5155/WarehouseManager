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
        'picking' => 'Đang soạn',
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

    $orderDemand = [];
    foreach ($batch->batchOrders as $batchOrder) {
        foreach ($batchOrder->fulfillmentOrder?->items ?? [] as $item) {
            $catalogId = (int) $item->product_catalog_id;
            $orderDemand[$catalogId] = ($orderDemand[$catalogId] ?? 0) + (int) $item->quantity;
        }
    }
@endphp

<div class="container-fluid px-1 px-md-2" id="deliveryBatchShowPage">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Chi tiết chuyến {{ $batch->batch_code }}</h3>
            <span class="badge text-bg-{{ $statusClass[$batch->status] ?? 'secondary' }}">{{ $statusLabel[$batch->status] ?? $batch->status }}</span>
        </div>
        <a href="{{ route('delivery.batches.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Danh sách chuyến
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Thông tin chuyến</h5>
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="vstack gap-2">
                                <div class="d-flex justify-content-between gap-3">
                                    <span class="text-muted">Nhân viên giao</span>
                                    <strong>{{ $batch->deliveryUser?->displayName() ?: '-' }}</strong>
                                </div>
                                <div class="d-flex justify-content-between gap-3">
                                    <span class="text-muted">Phương tiện</span>
                                    <strong>{{ $batch->vehicle?->displayName() ?: '-' }}</strong>
                                </div>
                                @if($batch->vehicle?->vehicle_type === 'car')
                                    <div class="d-flex justify-content-between gap-3">
                                        <span class="text-muted">Trọng tải</span>
                                        <strong>{{ $batch->vehicle->load_capacity ? number_format($batch->vehicle->load_capacity, 2) : '-' }}</strong>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-muted small mb-1">Ghi chú</div>
                                    <div class="border rounded p-2 bg-light">{{ $batch->delivery_note ?: ($batch->note ?: '-') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            @if($canManageDeliveryBatches)
                                <form method="POST" action="{{ route('delivery.batches.update', $batch) }}" class="row g-2 align-items-end">
                                    @csrf
                                    @method('PATCH')
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Nhân viên giao</label>
                                        <select name="delivery_user_id" class="form-select">
                                            <option value="">Chưa gán</option>
                                            @foreach($deliveryUsers as $user)
                                                <option value="{{ $user->id }}" @selected((int) $batch->delivery_user_id === (int) $user->id)>{{ $user->displayName() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Phương tiện</label>
                                        <select name="vehicle_id" class="form-select">
                                            <option value="">Không chọn</option>
                                            @foreach($activeVehicles as $vehicle)
                                                <option value="{{ $vehicle->id }}" @selected((int) $batch->vehicle_id === (int) $vehicle->id)>{{ $vehicle->displayName() }}{{ $vehicle->vehicle_type === 'car' && $vehicle->load_capacity ? ' / ' . number_format($vehicle->load_capacity, 2) : '' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Ghi chú</label>
                                        <input type="text" name="delivery_note" value="{{ $batch->delivery_note }}" class="form-control">
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button class="btn btn-primary fw-semibold" type="submit">Lưu chuyến</button>
                                    </div>
                                </form>
                            @else
                                <div class="text-muted">Bạn chỉ có quyền xem chuyến được gán.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                        <h5 class="fw-bold mb-0">Đơn trong chuyến</h5>
                        @if($canManageDeliveryBatches)
                            <form class="delivery-api-form d-flex gap-2 flex-column flex-md-row" method="POST" data-method="POST" data-endpoint="{{ route('delivery.batches.orders.store', $batch) }}" data-success-reload="true">
                                @csrf
                                <select name="fulfillment_order_id" class="form-select" required>
                                    <option value="">Chọn đơn</option>
                                    @foreach($availableOrders as $order)
                                        <option value="{{ $order->id }}">{{ $order->order_code }} - {{ $order->buyer_name ?: '-' }} (SL {{ $order->total_quantity ?? 0 }})</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-primary fw-semibold text-nowrap" type="submit">
                                    <i class="bi bi-plus-lg me-1"></i>Thêm đơn vào chuyến
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Khách</th>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batch->batchOrders as $batchOrder)
                                    @php($order = $batchOrder->fulfillmentOrder)
                                    <tr>
                                        <td class="fw-bold text-primary">{{ $order->order_code }}</td>
                                        <td>{{ $order->buyer_name ?: '-' }}</td>
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                @foreach($order->items as $item)
                                                    <span>{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }} x {{ number_format($item->quantity) }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                                @if($batch->status === 'out_for_delivery')
                                                    <a href="{{ route('delivery.orders.index') }}" class="btn btn-success btn-sm fw-semibold">
                                                        <i class="bi bi-truck me-1"></i>Xác nhận giao hàng
                                                    </a>
                                                @endif
                                                @if($canManageDeliveryBatches && $batchOrder->status !== 'delivered')
                                                    <form class="delivery-api-form" method="POST" data-method="DELETE" data-endpoint="{{ route('delivery.batches.orders.remove', $batchOrder) }}" data-success-reload="true" data-confirm="Hủy đơn khỏi chuyến giao này? Đơn sẽ quay về Chờ giao.">
                                                        @csrf
                                                        <button type="submit" class="btn btn-outline-danger btn-sm fw-semibold">Hủy đơn</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">Chưa có đơn trong chuyến.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                        <h5 class="fw-bold mb-0">Hàng / SN trong chuyến</h5>
                        @if($canManageDeliveryBatches)
                            <form class="delivery-api-form" method="POST" data-method="PATCH" data-endpoint="{{ route('delivery.batches.ready', $batch) }}" data-success-reload="true">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary fw-semibold">
                                    <i class="bi bi-check2-circle me-1"></i>Đánh dấu sẵn sàng giao
                                </button>
                            </form>
                        @endif
                    </div>

                    @if($canManageDeliveryBatches)
                        <form class="delivery-api-form mb-3" method="POST" data-method="POST" data-endpoint="{{ route('delivery.batches.serials.reserve', $batch) }}" data-success-reload="true" data-serial-scan-form="serials">
                            @csrf
                            <label class="form-label fw-semibold">Quét SN nạp vào chuyến</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                <input type="text" name="serials[]" class="form-control" placeholder="Quét SN" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-delivery-serial-scan>
                            </div>
                            <div class="form-text">Scanner gửi Enter/Tab sẽ tự nạp SN vào pool chuyến. Không gán SN vào đơn ở bước này.</div>
                        </form>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Nhu cầu</th>
                                    <th class="text-end">Tổng</th>
                                    <th>Phân loại</th>
                                    <th>Còn trên chuyến</th>
                                    <th>Đã giao</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($batch->serials->groupBy('product_catalog_id') as $catalogId => $catalogSerials)
                                    @php($catalog = $catalogSerials->first()->productCatalog)
                                    @php($openSerials = $catalogSerials->whereIn('status', ['reserved', 'assigned']))
                                    @php($deliveredSerials = $catalogSerials->where('status', 'delivered'))
                                    @php($demand = $orderDemand[(int) $catalogId] ?? 0)
                                    <tr>
                                        <td class="fw-semibold">{{ $catalog?->product_name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($demand) }}</td>
                                        <td class="text-end">{{ number_format($catalogSerials->count()) }}</td>
                                        <td>
                                            @if($demand === 0)
                                                <span class="badge text-bg-warning">Hàng không đơn</span>
                                            @elseif($catalogSerials->count() > $demand)
                                                <span class="badge text-bg-info">Vượt nhu cầu</span>
                                            @else
                                                <span class="badge text-bg-light border">Theo đơn</span>
                                            @endif
                                        </td>
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
                                    <tr><td colspan="6" class="text-center text-muted py-3">Chưa có SN trong chuyến.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/delivery-batches.js'])
@endpush
