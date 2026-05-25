@extends('layouts.admin')

@section('title', 'Chuyến giao')

@section('content')
@php
    $statusClass = [
        'draft' => 'secondary',
        'picking' => 'info',
        'ready' => 'primary',
        'out_for_delivery' => 'warning',
        'completed' => 'success',
        'cancelled' => 'dark',
    ];
    $statusLabel = [
        'draft' => 'Nháp',
        'picking' => 'Đang chuẩn bị',
        'ready' => 'Sẵn sàng',
        'out_for_delivery' => 'Đang giao',
        'completed' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
@endphp

<div class="container-fluid px-1 px-md-2" id="deliveryBatchesIndexPage">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="fw-bold text-dark mb-0">Chuyến giao</h3>
        <div class="d-flex gap-2">
            <a href="{{ route('delivery.orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-list-check me-1"></i>Đơn giao
            </a>
            @if(auth()->user()?->canManageDeliveryBatches())
                <button type="button" class="btn btn-primary fw-semibold" data-bs-toggle="collapse" data-bs-target="#createDeliveryBatchPanel">
                    <i class="bi bi-plus-lg me-1"></i>Tạo chuyến
                </button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    @if(auth()->user()?->canManageDeliveryBatches())
        <div class="collapse mb-3" id="createDeliveryBatchPanel">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form class="delivery-api-form row g-2 align-items-end" method="POST" data-method="POST" data-endpoint="{{ route('delivery.batches.store') }}" data-success-reload="true">
                        @csrf
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Nhân viên giao</label>
                            <select name="delivery_user_id" class="form-select">
                                <option value="">Chưa gán</option>
                                @foreach($deliveryUsers as $user)
                                    <option value="{{ $user->id }}">{{ $user->displayName() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Phương tiện</label>
                            <select name="vehicle_id" class="form-select">
                                <option value="">Không chọn</option>
                                @foreach($activeVehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->displayName() }}{{ $vehicle->vehicle_type === 'car' && $vehicle->load_capacity ? ' / ' . number_format($vehicle->load_capacity, 2) : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Ghi chú</label>
                            <input type="text" name="delivery_note" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary w-100 fw-semibold" type="submit">Tạo chuyến</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Mã chuyến</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Số đơn</th>
                            <th class="text-end">SN đã giữ</th>
                            <th>Nhân viên giao</th>
                            <th>Phương tiện</th>
                            <th>Ngày tạo</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td class="fw-bold">{{ $batch->batch_code }}</td>
                                <td><span class="badge text-bg-{{ $statusClass[$batch->status] ?? 'secondary' }}">{{ $statusLabel[$batch->status] ?? $batch->status }}</span></td>
                                <td class="text-end">{{ number_format($batch->batch_orders_count) }}</td>
                                <td class="text-end">{{ number_format($batch->serials_count) }}</td>
                                <td>{{ $batch->deliveryUser?->displayName() ?: '-' }}</td>
                                <td>{{ $batch->vehicle?->displayName() ?: '-' }}</td>
                                <td>{{ optional($batch->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('delivery.batches.show', $batch) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye me-1"></i>Chi tiết
                                        </a>
                                        @if(auth()->user()?->canManageDeliveryBatches())
                                            <form method="POST" action="{{ route('delivery.batches.cancel', $batch) }}" onsubmit="return confirm('Hủy chuyến giao này? Đơn trong chuyến sẽ được giữ lại và quay về Chờ giao.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Hủy chuyến</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Chưa có chuyến giao.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white">{{ $batches->links() }}</div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/delivery-batches.js'])
@endpush
