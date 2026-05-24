@extends('layouts.admin')

@section('title', 'Truy vet Serial')

@section('content')
@php
    $importedAt = $product?->imported_at ?: $product?->created_at;
    $exportedAt = $product?->exported_at ?: $exportVoucher?->exported_at;
@endphp

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <div class="text-uppercase text-primary fw-bold small mb-1">Quan ly kho</div>
        <h3 class="fw-bold text-dark m-0">Truy vet Serial</h3>
        <div class="text-muted small mt-1">Tra cuu vong doi mot serial tu luc nhap kho den khi xuat kho.</div>

        <form method="GET" action="{{ route('serial.trace.search') }}" class="row g-2 mt-3">
            <div class="col-md-8 col-lg-6">
                <input type="text" name="serial_number" value="{{ $serial }}" class="form-control form-control-lg" placeholder="Nhap hoac quet Serial Number" autofocus required>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary btn-lg fw-bold w-100">
                    <i class="bi bi-upc-scan me-1"></i>Tra cuu
                </button>
            </div>
        </form>
    </div>

    @if($serial && !$product)
        <div class="alert alert-warning border-0 shadow-sm rounded-4">
            Khong tim thay serial <strong>{{ $serial }}</strong> trong he thong.
        </div>
    @endif

    @if($product)
        <div class="row g-2 g-md-3 mb-3">
            <div class="col-12 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Serial</div><div class="fw-bold text-primary text-break">{{ $product->serial_number }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Trang thai</div><div class="fw-bold {{ $product->status == 1 ? 'text-success' : 'text-primary' }}">{{ $statusText }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">San pham</div><div class="fw-bold text-dark">{{ $importVoucherItem?->product_name_snapshot ?: ($exportVoucherItem?->product_name_snapshot ?: ($product->productCatalog?->product_name ?: 'N/A')) }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Vi tri hien tai</div><div class="fw-bold text-dark">{{ $product->status == 1 ? ($product->location?->shelf_name ?: 'N/A') : 'Da xuat kho' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Nha cung cap</div><div class="fw-bold text-dark">{{ $product->supplier?->name ?: 'N/A' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Ngay nhap</div><div class="fw-bold text-dark">{{ optional($importedAt)->format('d/m/Y H:i') ?: 'N/A' }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Ngay xuat</div><div class="fw-bold text-dark">{{ optional($exportedAt)->format('d/m/Y H:i') ?: 'Chua xuat' }}</div></div></div>
            @if($canViewCost)
                <div class="col-12 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Gia von catalog</div><div class="fw-bold text-dark">{{ number_format($product->productCatalog?->wholesale_price ?? 0) }} d</div></div></div>
            @endif
        </div>

        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3">
                        <h5 class="fw-bold text-dark">Phieu nhap</h5>
                        @if($importVoucher)
                            <a href="{{ route('reports.warehouse_history.imports.show', $importVoucher) }}" class="fw-bold text-primary">{{ $importVoucher->import_code }}</a>
                            <div class="text-muted small">{{ optional($importVoucher->imported_at)->format('d/m/Y H:i') }}</div>
                            <div class="mt-2">{{ $importVoucher->supplier?->name ?: $product->supplier?->name }}</div>
                            @if($importVoucherItem)
                                <div class="small text-muted mt-2">Dong phieu nhap #{{ $importVoucherItem->id }} - SL {{ $importVoucherItem->quantity }}</div>
                                @if($canViewCost)
                                    <div class="small text-muted">Don gia von {{ number_format($importVoucherItem->unit_cost ?? 0) }} d - Tong {{ number_format($importVoucherItem->total_cost ?? 0) }} d</div>
                                @endif
                            @endif
                        @else
                            <div class="text-muted">Chua co phieu nhap lien ket.</div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3">
                        <h5 class="fw-bold text-dark">Phieu xuat</h5>
                        @if($exportVoucher)
                            <a href="{{ route('export.print', $exportVoucher->id) }}" class="fw-bold text-primary">{{ $exportVoucher->export_code }}</a>
                            <div class="text-muted small">{{ optional($exportVoucher->exported_at)->format('d/m/Y H:i') }}</div>
                            <div class="mt-2">{{ $exportVoucher->buyer_name ?: $exportVoucher->company_name }}</div>
                            @if($exportVoucherItem)
                                <div class="small text-muted mt-2">Dong phieu xuat #{{ $exportVoucherItem->id }} - SL {{ $exportVoucherItem->quantity }}</div>
                                <div class="small text-muted">Don gia ban {{ number_format($exportVoucherItem->unit_price ?? 0) }} d</div>
                                @if($canViewCost)
                                    <div class="small text-muted">Gia von {{ number_format($exportVoucherItem->unit_cost ?? 0) }} d - Tong von {{ number_format($exportVoucherItem->total_cost ?? 0) }} d</div>
                                @endif
                            @endif
                        @else
                            <div class="text-muted">Serial nay chua xuat kho.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-3">
                <h5 class="fw-bold text-dark mb-3">Timeline stock_movements</h5>
                @forelse($movements as $movement)
                    <div class="d-flex gap-3 border-bottom py-3">
                        <div class="rounded-circle {{ $movement->movement_type === 'import' ? 'bg-success' : 'bg-primary' }} flex-shrink-0" style="width:12px;height:12px;margin-top:6px;"></div>
                        <div>
                            <div class="fw-bold">{{ optional($movement->occurred_at)->format('d/m/Y H:i') }} - {{ $movement->movement_type === 'import' ? 'Nhap kho' : 'Xuat kho' }}</div>
                            <div class="text-muted small">
                                {{ $movement->from_status ?? 'N/A' }} -> {{ $movement->to_status ?? 'N/A' }}
                                | {{ $movement->fromLocation?->shelf_name ?: 'N/A' }} -> {{ $movement->toLocation?->shelf_name ?: 'N/A' }}
                                | {{ $movement->importVoucher?->import_code ?: $movement->exportVoucher?->export_code ?: 'Khong co ma phieu' }}
                                | {{ $movement->user?->displayName() ?: 'He thong' }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-muted">Chua co movement cho serial nay. Thong tin product co ban van duoc hien thi tu products va cac bang voucher WMS v2.</div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@endsection
