@extends('layouts.admin')

@section('title', 'Lịch sử kho')

@section('content')
@php
    $typeLabels = ['import' => 'Nhập kho', 'export' => 'Xuất kho'];
    $statusLabels = [1 => 'Còn trong kho', 2 => 'Đã xuất kho'];
@endphp

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="text-uppercase text-primary fw-bold small mb-1">Báo cáo - Thống kê</div>
                <h3 class="fw-bold text-dark m-0">Lịch sử kho</h3>
                <div class="text-muted small mt-1">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.warehouse_history') }}" class="row g-2 mt-3">
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Từ ngày</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Đến ngày</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Loại</label>
                <select name="movement_type" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="import" @selected(request('movement_type') === 'import')>Nhập kho</option>
                    <option value="export" @selected(request('movement_type') === 'export')>Xuất kho</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Serial</label>
                <input type="text" name="serial_number" value="{{ request('serial_number') }}" class="form-control" placeholder="SN...">
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Sản phẩm</label>
                <input type="text" name="product_name" value="{{ request('product_name') }}" class="form-control" placeholder="Tên hàng">
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Mã phiếu</label>
                <input type="text" name="voucher_code" value="{{ request('voucher_code') }}" class="form-control" placeholder="PN/PX">
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Người thao tác</label>
                <select name="user_id" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach($users as $filterUser)
                        <option value="{{ $filterUser->id }}" @selected((string) request('user_id') === (string) $filterUser->id)>
                            {{ $filterUser->display_name ?: $filterUser->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <button class="btn btn-primary fw-bold">
                    <i class="bi bi-funnel me-1"></i>Lọc lịch sử
                </button>
            </div>
        </form>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Tổng movement</div><div class="fs-4 fw-bold">{{ number_format($summary->total_movements ?? 0) }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">SN nhập</div><div class="fs-4 fw-bold text-success">{{ number_format($summary->imported_qty ?? 0) }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">SN xuất</div><div class="fs-4 fw-bold text-primary">{{ number_format($summary->exported_qty ?? 0) }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Sản phẩm ảnh hưởng</div><div class="fs-4 fw-bold">{{ number_format($summary->product_count ?? 0) }}</div></div></div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-3">
        <div class="card-body p-3">
            <h5 class="fw-bold text-dark mb-3">Tổng hợp theo ngày</h5>
            <div class="row g-2">
                @forelse($dailyGroups as $day)
                    <div class="col-md-6 col-xl-4">
                        <a class="d-block border rounded-3 p-3 text-decoration-none bg-light h-100" href="{{ route('reports.warehouse_history', array_merge(request()->query(), ['start_date' => $day->movement_date, 'end_date' => $day->movement_date])) }}">
                            <div class="fw-bold text-dark">{{ \Carbon\Carbon::parse($day->movement_date)->format('d/m/Y') }}</div>
                            <div class="small text-muted mt-1">{{ number_format($day->import_count) }} lần nhập · {{ number_format($day->export_count) }} lần xuất · {{ number_format($day->imported_qty) }} SN nhập · {{ number_format($day->exported_qty) }} SN xuất</div>
                            <div class="small text-primary fw-bold mt-2">Xem chi tiết</div>
                        </a>
                    </div>
                @empty
                    <div class="col-12 text-center text-muted py-3">
                        Không có dữ liệu trong khoảng thời gian này. Hãy mở rộng khoảng ngày.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <h5 class="fw-bold text-dark mb-3">Chi tiết theo sản phẩm</h5>
            @forelse($productGroups as $productName => $groupMovements)
                <div class="border rounded-3 mb-3 overflow-hidden">
                    <div class="bg-light p-3 d-flex flex-column flex-md-row justify-content-between gap-2">
                        <div class="fw-bold text-dark">{{ $productName }}</div>
                        <div class="small text-muted">
                            Nhập: {{ number_format($groupMovements->where('movement_type', 'import')->sum('quantity')) }} SN ·
                            Xuất: {{ number_format($groupMovements->where('movement_type', 'export')->sum('quantity')) }} SN
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Serial</th>
                                    <th>Loại</th>
                                    <th>Trạng thái hiện tại</th>
                                    <th>Vị trí / Phiếu</th>
                                    <th>Thời gian</th>
                                    <th>Người thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupMovements as $movement)
                                    <tr>
                                        <td><a href="{{ route('serial.trace.search', ['serial_number' => $movement->serial_number]) }}" class="fw-bold text-primary">{{ $movement->serial_number }}</a></td>
                                        <td><span class="badge {{ $movement->movement_type === 'import' ? 'bg-success' : 'bg-primary' }}">{{ $typeLabels[$movement->movement_type] ?? $movement->movement_type }}</span></td>
                                        <td>{{ $statusLabels[$movement->product?->status] ?? 'N/A' }}</td>
                                        <td>
                                            @if($movement->movement_type === 'import')
                                                <span class="text-muted">{{ $movement->toLocation?->shelf_name ?: 'N/A' }}</span>
                                                @if($movement->importVoucher)
                                                    <a class="d-block small" href="{{ route('reports.warehouse_history.imports.show', $movement->importVoucher) }}">{{ $movement->importVoucher->import_code }}</a>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ $movement->fromLocation?->shelf_name ?: 'N/A' }}</span>
                                                @if($movement->exportVoucher)
                                                    <a class="d-block small" href="{{ route('export.print', $movement->exportVoucher->id) }}">{{ $movement->exportVoucher->export_code }}</a>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ optional($movement->occurred_at)->format('d/m/Y H:i') }}</td>
                                        <td>{{ $movement->user?->displayName() ?: 'Hệ thống' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">
                    Không có dữ liệu trong khoảng thời gian này. Hãy mở rộng khoảng ngày.
                </div>
            @endforelse

            <div class="mt-3">{{ $movements->links() }}</div>
        </div>
    </div>
</div>
@endsection
