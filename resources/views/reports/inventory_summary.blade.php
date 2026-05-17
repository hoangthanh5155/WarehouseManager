@extends('layouts.admin')

@section('title', 'Nhập xuất tồn')

@section('content')
@php
    $formatQty = fn ($value) => number_format((float) $value);
    $formatMoney = fn ($value) => number_format((float) $value) . ' đ';
@endphp

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
            <div>
                <div class="text-uppercase text-primary fw-bold small mb-1">Báo cáo - Thống kê</div>
                <h3 class="fw-bold text-dark m-0">Nhập xuất tồn</h3>
                <div class="text-muted small mt-1">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</div>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.inventory_summary') }}" class="row g-2 mt-3">
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Từ ngày</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-6 col-md-2">
                <label class="small text-muted fw-bold">Đến ngày</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-12 col-md-3">
                <label class="small text-muted fw-bold">Sản phẩm</label>
                <input type="text" name="product_name" value="{{ request('product_name') }}" class="form-control" placeholder="Tên sản phẩm">
            </div>
            <div class="col-12 col-md-3">
                <label class="small text-muted fw-bold">Nhà cung cấp</label>
                <select name="supplier_id" class="form-select">
                    <option value="">Tất cả</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button class="btn btn-primary fw-bold w-100">
                    <i class="bi bi-funnel me-1"></i>Lọc
                </button>
            </div>
        </form>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-2"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Tổng sản phẩm</div><div class="fs-4 fw-bold">{{ $formatQty($totals['product_count']) }}</div></div></div>
        <div class="col-6 col-lg-2"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Tồn đầu kỳ</div><div class="fs-4 fw-bold">{{ $formatQty($totals['opening_qty']) }}</div></div></div>
        <div class="col-6 col-lg-2"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Nhập trong kỳ</div><div class="fs-4 fw-bold text-success">{{ $formatQty($totals['imported_qty']) }}</div></div></div>
        <div class="col-6 col-lg-2"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Xuất trong kỳ</div><div class="fs-4 fw-bold text-primary">{{ $formatQty($totals['exported_qty']) }}</div></div></div>
        <div class="col-6 col-lg-2"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Tồn cuối kỳ</div><div class="fs-4 fw-bold">{{ $formatQty($totals['closing_qty']) }}</div></div></div>
        @if($canViewCost)
            <div class="col-6 col-lg-2"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Giá trị tồn cuối</div><div class="fs-6 fw-bold text-dark">{{ $formatMoney($totals['closing_value']) }}</div></div></div>
        @endif
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark m-0">Chi tiết theo sản phẩm</h5>
                <span class="badge bg-light text-dark border">{{ $formatQty($rows->total()) }} dòng</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Nhà cung cấp</th>
                            <th class="text-end">Tồn đầu kỳ</th>
                            <th class="text-end">Nhập</th>
                            <th class="text-end">Xuất</th>
                            <th class="text-end">Tồn cuối kỳ</th>
                            <th class="text-end">Tồn thực tế</th>
                            @if($canViewCost)
                                <th class="text-end">Giá nhập</th>
                                <th class="text-end">Giá trị tồn đầu</th>
                                <th class="text-end">Giá trị nhập</th>
                                <th class="text-end">Giá vốn xuất</th>
                                <th class="text-end">Giá trị tồn cuối</th>
                            @endif
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            @php
                                $openingValue = (float) $row->opening_qty * (float) $row->wholesale_price;
                                $importValue = (float) $row->imported_qty * (float) $row->wholesale_price;
                                $exportValue = (float) $row->exported_qty * (float) $row->wholesale_price;
                                $closingValue = (float) $row->closing_qty * (float) $row->wholesale_price;
                                $needsReconcile = (float) $row->closing_qty !== (float) $row->current_stock_qty;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $row->product_name }}</div>
                                    @if($needsReconcile)
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1">Cần đối soát</span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $row->supplier_name ?: 'N/A' }}</td>
                                <td class="text-end fw-bold">{{ $formatQty($row->opening_qty) }}</td>
                                <td class="text-end text-success fw-bold">{{ $formatQty($row->imported_qty) }}</td>
                                <td class="text-end text-primary fw-bold">{{ $formatQty($row->exported_qty) }}</td>
                                <td class="text-end fw-bold">{{ $formatQty($row->closing_qty) }}</td>
                                <td class="text-end fw-bold">{{ $formatQty($row->current_stock_qty) }}</td>
                                @if($canViewCost)
                                    <td class="text-end">{{ $formatMoney($row->wholesale_price) }}</td>
                                    <td class="text-end">{{ $formatMoney($openingValue) }}</td>
                                    <td class="text-end">{{ $formatMoney($importValue) }}</td>
                                    <td class="text-end">{{ $formatMoney($exportValue) }}</td>
                                    <td class="text-end">{{ $formatMoney($closingValue) }}</td>
                                @endif
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('reports.warehouse_history', ['product_catalog_id' => $row->id, 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="bi bi-clock-history me-1"></i>Lịch sử
                                    </a>
                                    @if(auth()->user()?->canAccessFullProductDetail())
                                        <a href="{{ route('products.showCatalog', $row->id) }}" class="btn btn-sm btn-outline-secondary fw-bold">
                                            <i class="bi bi-eye me-1"></i>Sản phẩm
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewCost ? 13 : 8 }}" class="text-center text-muted py-4">
                                    Không có dữ liệu nhập xuất tồn trong khoảng thời gian này.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $rows->links() }}</div>
        </div>
    </div>
</div>
@endsection
