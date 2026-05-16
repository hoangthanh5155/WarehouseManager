@extends('layouts.admin')

@section('title', 'Doanh thu & dòng tiền kho')

@section('content')
<style>
    .report-shell { max-width: 1320px; margin: 0 auto; }
    .report-header,
    .report-panel,
    .kpi-card,
    .voucher-mobile-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    }
    .range-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        border: 1px solid #dbe3ef;
        background: #f8fafc;
        color: #334155;
        font-size: 0.86rem;
        font-weight: 700;
        padding: 8px 13px;
        text-decoration: none;
        white-space: nowrap;
    }
    .range-chip.active,
    .range-chip:hover {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #ffffff;
    }
    .range-chip-row {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .range-chip-row::-webkit-scrollbar { display: none; }
    .kpi-card { padding: 16px; height: 100%; }
    .kpi-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .kpi-label {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .kpi-value {
        color: #0f172a;
        font-size: 1.35rem;
        font-weight: 800;
        overflow-wrap: anywhere;
    }
    .voucher-mobile-card { padding: 14px; }
    .money-positive { color: #047857; }
    .money-negative { color: #dc2626; }
    @media (max-width: 575.98px) {
        .report-header,
        .report-panel {
            border-radius: 14px;
            padding: 12px !important;
        }
        .report-title { font-size: 1.2rem; }
        .range-chip { padding: 6px 10px; font-size: 0.76rem; }
        .kpi-card { padding: 12px; border-radius: 14px; }
        .kpi-icon { width: 34px; height: 34px; border-radius: 10px; font-size: 1rem; }
        .kpi-label { font-size: 0.68rem; }
        .kpi-value { font-size: 1rem; }
    }
</style>

@php
    $formatMoney = fn ($value) => number_format((float) $value) . ' đ';
    $periodLinks = [
        'today' => 'Hôm nay',
        '7days' => '7 ngày',
        'month' => 'Tháng này',
        'year' => 'Năm nay',
    ];
@endphp

<div class="report-shell container-fluid px-1 px-md-2 mb-5">
    <div class="report-header p-3 p-md-4 mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div>
                <div class="text-uppercase text-primary fw-bold small mb-1">Báo cáo - Thống kê</div>
                <h3 class="report-title fw-bold text-dark m-0">DOANH THU & DÒNG TIỀN KHO</h3>
                <div class="text-muted mt-1 small">
                    {{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}
                </div>
            </div>
        </div>

        <div class="range-chip-row mt-3">
            @foreach($periodLinks as $periodValue => $periodLabel)
                <a href="{{ route('reports.revenue', ['period' => $periodValue]) }}" class="range-chip {{ $period === $periodValue ? 'active' : '' }}">
                    {{ $periodLabel }}
                </a>
            @endforeach
            <a href="{{ route('reports.revenue', ['period' => 'custom', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="range-chip {{ $period === 'custom' ? 'active' : '' }}">
                Từ - đến
            </a>
        </div>

        <form action="{{ route('reports.revenue') }}" method="GET" class="row g-2 align-items-end mt-3 {{ $period === 'custom' ? '' : 'd-none' }}">
            <input type="hidden" name="period" value="custom">
            <div class="col-6 col-md-3">
                <label class="small text-muted fw-bold mb-1">Từ ngày</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <label class="small text-muted fw-bold mb-1">Đến ngày</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary fw-bold px-4 w-100">
                    <i class="bi bi-funnel me-1"></i> Lọc
                </button>
            </div>
        </form>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex align-items-start gap-3">
                    <div class="kpi-icon bg-primary-subtle text-primary"><i class="bi bi-cash-stack"></i></div>
                    <div class="min-w-0">
                        <div class="kpi-label">Doanh thu</div>
                        <div class="kpi-value">{{ $formatMoney($totalRevenue) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex align-items-start gap-3">
                    <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-receipt"></i></div>
                    <div class="min-w-0">
                        <div class="kpi-label">Giá vốn</div>
                        <div class="kpi-value">{{ $formatMoney($totalCost) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex align-items-start gap-3">
                    <div class="kpi-icon {{ $grossProfit >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="min-w-0">
                        <div class="kpi-label">Lợi nhuận gộp</div>
                        <div class="kpi-value {{ $grossProfit >= 0 ? 'money-positive' : 'money-negative' }}">{{ $formatMoney($grossProfit) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="kpi-card">
                <div class="d-flex align-items-start gap-3">
                    <div class="kpi-icon bg-info-subtle text-info"><i class="bi bi-file-earmark-text"></i></div>
                    <div class="min-w-0">
                        <div class="kpi-label">Số phiếu xuất</div>
                        <div class="kpi-value">{{ number_format($exportOrderCount) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12">
            <div class="kpi-card">
                <div class="d-flex align-items-start gap-3">
                    <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-box-arrow-in-down"></i></div>
                    <div>
                        <div class="kpi-label">Giá trị nhập ước tính</div>
                        <div class="kpi-value">{{ $formatMoney($estimatedImportValue) }}</div>
                        <div class="text-muted small mt-1">Tính theo số sản phẩm được tạo trong kỳ nhân với giá nhập hiện tại trong danh mục.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="report-panel p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark m-0">Phiếu xuất trong kỳ</h5>
            <span class="badge bg-light text-dark border">{{ number_format($vouchers->total()) }} phiếu</span>
        </div>

        <div class="table-responsive d-none d-md-block">
            <table class="table table-hover align-middle m-0">
                <thead class="table-light">
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Khách hàng</th>
                        <th>Loại khách</th>
                        <th class="text-end">Doanh thu</th>
                        <th class="text-end">Giá vốn</th>
                        <th class="text-end">Lãi gộp</th>
                        <th>Ngày xuất</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        @php
                            $voucherProfit = (float) $voucher->total_amount - (float) $voucher->total_cost;
                            $customerName = $voucher->company_name ?: $voucher->buyer_name;
                        @endphp
                        <tr>
                            <td class="fw-bold text-primary">{{ $voucher->export_code }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $customerName ?: 'N/A' }}</div>
                                @if($voucher->company_name && $voucher->buyer_name)
                                    <small class="text-muted">{{ $voucher->buyer_name }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $voucher->customer_type === 'agency' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $voucher->customer_type === 'agency' ? 'Đại lý' : 'Khách lẻ' }}
                                </span>
                            </td>
                            <td class="text-end fw-bold">{{ $formatMoney($voucher->total_amount) }}</td>
                            <td class="text-end text-muted">{{ $formatMoney($voucher->total_cost) }}</td>
                            <td class="text-end fw-bold {{ $voucherProfit >= 0 ? 'money-positive' : 'money-negative' }}">{{ $formatMoney($voucherProfit) }}</td>
                            <td class="text-nowrap">{{ optional($voucher->exported_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('export.print', $voucher->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                    <i class="bi bi-eye me-1"></i> Xem hóa đơn
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Không có phiếu xuất trong khoảng thời gian này.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-md-none d-flex flex-column gap-2">
            @forelse($vouchers as $voucher)
                @php
                    $voucherProfit = (float) $voucher->total_amount - (float) $voucher->total_cost;
                    $customerName = $voucher->company_name ?: $voucher->buyer_name;
                @endphp
                <div class="voucher-mobile-card">
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-bold text-primary">{{ $voucher->export_code }}</div>
                            <div class="small text-muted">{{ optional($voucher->exported_at)->format('d/m/Y H:i') }}</div>
                        </div>
                        <span class="badge align-self-start {{ $voucher->customer_type === 'agency' ? 'bg-success' : 'bg-danger' }}">
                            {{ $voucher->customer_type === 'agency' ? 'Đại lý' : 'Khách lẻ' }}
                        </span>
                    </div>

                    <div class="fw-bold text-dark mb-2">{{ $customerName ?: 'N/A' }}</div>

                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <div class="text-muted">Doanh thu</div>
                            <div class="fw-bold">{{ $formatMoney($voucher->total_amount) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted">Giá vốn</div>
                            <div class="fw-bold">{{ $formatMoney($voucher->total_cost) }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted">Lãi gộp</div>
                            <div class="fw-bold {{ $voucherProfit >= 0 ? 'money-positive' : 'money-negative' }}">{{ $formatMoney($voucherProfit) }}</div>
                        </div>
                    </div>

                    <a href="{{ route('export.print', $voucher->id) }}" class="btn btn-outline-primary btn-sm fw-bold w-100">
                        <i class="bi bi-eye me-1"></i> Xem hóa đơn
                    </a>
                </div>
            @empty
                <div class="text-center text-muted py-4">Không có phiếu xuất trong khoảng thời gian này.</div>
            @endforelse
        </div>

        @if($vouchers->hasPages())
            <div class="d-flex justify-content-center mt-3">
                <nav aria-label="Revenue report pagination">
                    <ul class="pagination pagination-sm m-0 shadow-sm">
                        @if ($vouchers->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">« Trước</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $vouchers->previousPageUrl() }}" rel="prev">« Trước</a></li>
                        @endif

                        @foreach ($vouchers->getUrlRange(1, $vouchers->lastPage()) as $page => $url)
                            <li class="page-item {{ $page == $vouchers->currentPage() ? 'active' : '' }}">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach

                        @if ($vouchers->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $vouchers->nextPageUrl() }}" rel="next">Sau »</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">Sau »</span></li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
</div>
@endsection
