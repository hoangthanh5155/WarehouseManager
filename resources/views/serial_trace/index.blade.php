@extends('layouts.admin')

@section('title', 'Truy vết Serial')

@section('content')
@php
    $importedAt = $product?->imported_at ?: $product?->created_at;
    $exportedAt = $product?->exported_at ?: $exportVoucher?->exported_at;
    $stockStatusLabels = [1 => 'Còn trong kho', 2 => 'Đã xuất kho'];
@endphp

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <h3 class="fw-bold text-dark m-0">Truy vết Serial</h3>

        <form method="GET" action="{{ route('serial.trace.search') }}" class="row g-2 mt-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="serial_number" value="{{ $serial }}" class="form-control form-control-lg" placeholder="Nhập hoặc quét Serial" autofocus required>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary btn-lg fw-bold w-100">
                    <i class="bi bi-upc-scan me-1"></i>Tra cứu
                </button>
            </div>
        </form>
    </div>

    @if($serial && !$product)
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            Không tìm thấy serial <strong>{{ $serial }}</strong>.
        </div>
    @endif

    @if($product)
        <div class="row g-2 g-md-3 mb-3">
            <div class="col-12 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Serial</div><div class="fw-bold text-primary text-break">{{ $product->serial_number }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Trạng thái</div><div class="fw-bold {{ $product->status == 1 ? 'text-success' : 'text-primary' }}">{{ $statusText }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Sản phẩm</div><div class="fw-bold text-dark">{{ $importVoucherItem?->product_name_snapshot ?: ($exportVoucherItem?->product_name_snapshot ?: ($product->productCatalog?->product_name ?: 'N/A')) }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Vị trí hiện tại</div><div class="fw-bold text-dark">{{ $product->status == 1 ? ($product->location?->shelf_name ?: 'N/A') : 'Đã xuất kho' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Nhà cung cấp</div><div class="fw-bold text-dark">{{ $product->supplier?->name ?: 'N/A' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Ngày nhập</div><div class="fw-bold text-dark">{{ optional($importedAt)->format('d/m/Y H:i') ?: 'N/A' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Ngày xuất</div><div class="fw-bold text-dark">{{ optional($exportedAt)->format('d/m/Y H:i') ?: 'Chưa xuất' }}</div></div></div>
            @if($canViewCost)
                <div class="col-12 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Giá vốn</div><div class="fw-bold text-dark">{{ number_format($product->productCatalog?->wholesale_price ?? 0) }} đ</div></div></div>
            @endif
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3">
                        <h5 class="fw-bold text-dark">Phiếu nhập</h5>
                        @if($importVoucher)
                            <a href="{{ route('reports.warehouse_history.imports.show', $importVoucher) }}" class="fw-bold text-primary">{{ $importVoucher->import_code }}</a>
                            <div class="text-muted small">{{ optional($importVoucher->imported_at)->format('d/m/Y H:i') }}</div>
                            <div class="mt-2">{{ $importVoucher->supplier?->name ?: $product->supplier?->name }}</div>
                            @if($importVoucherItem)
                                <div class="small text-muted mt-2">Dòng #{{ $importVoucherItem->id }} · SL {{ $importVoucherItem->quantity }}</div>
                                @if($canViewCost)
                                    <div class="small text-muted">Đơn giá vốn {{ number_format($importVoucherItem->unit_cost ?? 0) }} đ · Tổng {{ number_format($importVoucherItem->total_cost ?? 0) }} đ</div>
                                @endif
                            @endif
                        @else
                            <div class="text-muted">Chưa có phiếu nhập.</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3">
                        <h5 class="fw-bold text-dark">Phiếu xuất</h5>
                        @if($exportVoucher)
                            <a href="{{ route('export.print', $exportVoucher->id) }}" class="fw-bold text-primary">{{ $exportVoucher->export_code }}</a>
                            <div class="text-muted small">{{ optional($exportVoucher->exported_at)->format('d/m/Y H:i') }}</div>
                            <div class="mt-2">{{ $exportVoucher->buyer_name ?: $exportVoucher->company_name }}</div>
                            @if($exportVoucherItem)
                                <div class="small text-muted mt-2">Dòng #{{ $exportVoucherItem->id }} · SL {{ $exportVoucherItem->quantity }}</div>
                                <div class="small text-muted">Đơn giá bán {{ number_format($exportVoucherItem->unit_price ?? 0) }} đ</div>
                                @if($canViewCost)
                                    <div class="small text-muted">Giá vốn {{ number_format($exportVoucherItem->unit_cost ?? 0) }} đ · Tổng vốn {{ number_format($exportVoucherItem->total_cost ?? 0) }} đ</div>
                                @endif
                            @endif
                        @else
                            <div class="text-muted">Chưa xuất kho.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-3">
                <h5 class="fw-bold text-dark mb-3">Lịch sử</h5>
                @forelse($movements as $movement)
                    <div class="d-flex gap-3 border-bottom py-3">
                        <div class="rounded-circle {{ $movement->movement_type === 'import' ? 'bg-success' : 'bg-primary' }} flex-shrink-0" style="width:12px;height:12px;margin-top:6px;"></div>
                        <div>
                            <div class="fw-bold">{{ optional($movement->occurred_at)->format('d/m/Y H:i') }} - {{ $movement->movement_type === 'import' ? 'Nhập kho' : 'Xuất kho' }}</div>
                            <div class="text-muted small">
                                {{ $stockStatusLabels[$movement->from_status] ?? 'N/A' }} → {{ $stockStatusLabels[$movement->to_status] ?? 'N/A' }}
                                | {{ $movement->fromLocation?->shelf_name ?: 'N/A' }} → {{ $movement->toLocation?->shelf_name ?: 'N/A' }}
                                | {{ $movement->importVoucher?->import_code ?: $movement->exportVoucher?->export_code ?: 'Không có mã phiếu' }}
                                | {{ $movement->user?->displayName() ?: 'Hệ thống' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Chưa có lịch sử.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
