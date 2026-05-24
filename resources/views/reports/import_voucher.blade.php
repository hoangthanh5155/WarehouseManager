@extends('layouts.admin')

@section('title', 'Chi tiet phieu nhap')

@section('content')
<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <a href="{{ route('reports.warehouse_history') }}" class="btn btn-sm btn-light bg-white border rounded-pill fw-bold px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i>Tro ve lich su kho
        </a>
        <div class="text-uppercase text-primary fw-bold small mb-1">Phieu nhap kho</div>
        <h3 class="fw-bold text-dark m-0">{{ $importVoucher->import_code }}</h3>
        <div class="text-muted small mt-1">{{ optional($importVoucher->imported_at)->format('d/m/Y H:i') }}</div>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Nha cung cap</div><div class="fw-bold text-dark">{{ $importVoucher->supplier?->name ?: 'N/A' }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Nguoi nhap</div><div class="fw-bold text-dark">{{ $importVoucher->user?->displayName() ?: 'He thong' }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">So luong</div><div class="fw-bold text-dark">{{ number_format($importVoucher->total_quantity) }}</div></div></div>
        @if($canViewCost)
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Tong gia von</div><div class="fw-bold text-dark">{{ number_format($importVoucher->total_cost) }} d</div></div></div>
        @endif
    </div>

    @forelse($importVoucher->items as $item)
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-3">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">{{ $item->productCatalog?->product_name ?: 'N/A' }}</h5>
                        <div class="text-muted small">{{ $item->location?->shelf_name ?: 'N/A' }} · SL {{ number_format($item->quantity) }}</div>
                    </div>
                    @if($canViewCost)
                        <div class="text-md-end">
                            <div class="small text-muted">Don gia von</div>
                            <div class="fw-bold">{{ number_format($item->unit_cost) }} d</div>
                            <div class="small text-muted">Tong {{ number_format($item->total_cost) }} d</div>
                        </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Serial</th>
                                <th>Trang thai hien tai</th>
                                <th>Vi tri hien tai</th>
                                <th>Phieu xuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($item->products as $product)
                                <tr>
                                    <td><a href="{{ route('serial.trace.search', ['serial_number' => $product->serial_number]) }}" class="fw-bold text-primary">{{ $product->serial_number }}</a></td>
                                    <td><span class="badge {{ $product->status == 1 ? 'bg-success' : 'bg-primary' }}">{{ $product->status == 1 ? 'Con trong kho' : 'Da xuat' }}</span></td>
                                    <td>{{ $product->status == 1 ? ($product->location?->shelf_name ?: 'N/A') : 'Da xuat' }}</td>
                                    <td>
                                        @if($product->exportVoucher)
                                            <a href="{{ route('export.print', $product->exportVoucher->id) }}">{{ $product->exportVoucher->export_code }}</a>
                                        @else
                                            <span class="text-muted">Chua xuat</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Khong co serial trong dong nhap nay.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body text-center text-muted py-4">Phieu nhap chua co dong hang.</div>
        </div>
    @endforelse
</div>
@endsection
