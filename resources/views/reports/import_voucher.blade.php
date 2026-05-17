@extends('layouts.admin')

@section('title', 'Chi tiết phiếu nhập')

@section('content')
<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <a href="{{ route('reports.warehouse_history') }}" class="btn btn-sm btn-light bg-white border rounded-pill fw-bold px-3 mb-3">
            <i class="bi bi-arrow-left me-1"></i>Trở về lịch sử kho
        </a>
        <div class="text-uppercase text-primary fw-bold small mb-1">Phiếu nhập kho</div>
        <h3 class="fw-bold text-dark m-0">{{ $importVoucher->import_code }}</h3>
        <div class="text-muted small mt-1">{{ optional($importVoucher->imported_at)->format('d/m/Y H:i') }}</div>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Nhà cung cấp</div><div class="fw-bold text-dark">{{ $importVoucher->supplier?->name ?: 'N/A' }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Sản phẩm</div><div class="fw-bold text-dark">{{ $importVoucher->productCatalog?->product_name ?: 'N/A' }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Vị trí</div><div class="fw-bold text-dark">{{ $importVoucher->location?->shelf_name ?: 'N/A' }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Số lượng</div><div class="fw-bold text-dark">{{ number_format($importVoucher->total_quantity) }}</div></div></div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <h5 class="fw-bold text-dark mb-3">Danh sách serial</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Serial</th>
                            <th>Trạng thái hiện tại</th>
                            <th>Vị trí hiện tại</th>
                            <th>Phiếu xuất</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($importVoucher->products as $product)
                            <tr>
                                <td><a href="{{ route('serial.trace.search', ['serial_number' => $product->serial_number]) }}" class="fw-bold text-primary">{{ $product->serial_number }}</a></td>
                                <td>
                                    <span class="badge {{ $product->status == 1 ? 'bg-success' : 'bg-primary' }}">
                                        {{ $product->status == 1 ? 'Còn trong kho' : 'Đã xuất' }}
                                    </span>
                                </td>
                                <td>{{ $product->status == 1 ? ($product->location?->shelf_name ?: 'N/A') : 'Đã xuất' }}</td>
                                <td>
                                    @if($product->exportVoucher)
                                        <a href="{{ route('export.print', $product->exportVoucher->id) }}">{{ $product->exportVoucher->export_code }}</a>
                                    @else
                                        <span class="text-muted">Chưa xuất</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Không có serial trong phiếu nhập này.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
