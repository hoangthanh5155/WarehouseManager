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
@endphp

<div class="container-fluid px-1 px-md-2" id="deliveryBatchesIndexPage">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Chuyến giao</h3>
            <div class="text-muted">Gom đơn, giữ serial và xác nhận giao thành công để tạo phiếu xuất.</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('delivery.orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-list-check me-1"></i>Đơn cần giao
            </a>
            <button type="button" class="btn btn-primary fw-semibold" data-create-delivery-batch data-endpoint="{{ route('delivery.batches.store') }}">
                <i class="bi bi-plus-lg me-1"></i>Tạo chuyến
            </button>
        </div>
    </div>

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
                            <th>Ngày tạo</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td class="fw-bold">{{ $batch->batch_code }}</td>
                                <td><span class="badge text-bg-{{ $statusClass[$batch->status] ?? 'secondary' }}">{{ $batch->status }}</span></td>
                                <td class="text-end">{{ number_format($batch->batch_orders_count) }}</td>
                                <td class="text-end">{{ number_format($batch->serials_count) }}</td>
                                <td>{{ optional($batch->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('delivery.batches.show', $batch) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>Chi tiết
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Chưa có chuyến giao.</td>
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
