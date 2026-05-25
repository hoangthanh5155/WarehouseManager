@extends('layouts.admin')

@section('title', 'Phương tiện giao hàng')

@section('content')
<div class="container-fluid px-1 px-md-2">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="fw-bold text-dark mb-0">Phương tiện giao hàng</h3>
        <a href="{{ route('delivery.vehicles.create') }}" class="btn btn-primary fw-semibold">
            <i class="bi bi-plus-lg me-1"></i>Tạo phương tiện
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Loại</th>
                            <th>Biển kiểm soát</th>
                            <th class="text-end">Trọng tải</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Số chuyến</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vehicles as $vehicle)
                            <tr>
                                <td class="fw-semibold">{{ $typeLabels[$vehicle->vehicle_type] ?? $vehicle->vehicle_type }}</td>
                                <td>{{ $vehicle->plate_number ?: '-' }}</td>
                                <td class="text-end">{{ $vehicle->load_capacity ? number_format($vehicle->load_capacity, 2) : '-' }}</td>
                                <td><span class="badge text-bg-{{ $vehicle->status === 'active' ? 'success' : 'secondary' }}">{{ $statusLabels[$vehicle->status] ?? $vehicle->status }}</span></td>
                                <td class="text-end">{{ number_format($vehicle->batches_count) }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <a href="{{ route('delivery.vehicles.edit', $vehicle) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                        <form method="POST" action="{{ route('delivery.vehicles.destroy', $vehicle) }}" onsubmit="return confirm('Xóa hoặc ngưng sử dụng phương tiện này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" type="submit">{{ $vehicle->batches_count ? 'Ngưng sử dụng' : 'Xóa' }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">Chưa có phương tiện.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($vehicles->hasPages())
            <div class="card-footer bg-white">{{ $vehicles->links() }}</div>
        @endif
    </div>
</div>
@endsection
