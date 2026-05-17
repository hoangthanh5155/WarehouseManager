@extends('layouts.admin')

@section('title', 'Tổng quan')

@section('content')
<style>
    .overview-shell { max-width: 1360px; margin: 0 auto; }
    .overview-hero {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 18px;
        box-shadow: 0 14px 36px rgba(15, 23, 42, 0.07);
        padding: 20px;
    }
    .overview-accent {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: #0d6efd;
        color: #fff;
        box-shadow: 0 12px 28px rgba(13, 110, 253, 0.24);
        font-size: 1.35rem;
        flex: 0 0 auto;
    }
    .overview-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.07);
        border-radius: 18px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
    }
    .overview-kpi { padding: 18px; height: 100%; }
    .overview-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex: 0 0 auto;
    }
    .overview-label {
        color: #64748b;
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .02em;
    }
    .overview-value {
        color: #0f172a;
        font-size: 1.45rem;
        line-height: 1.15;
        font-weight: 900;
        overflow-wrap: anywhere;
    }
    .overview-note { color: #64748b; font-size: 0.84rem; }
    .chart-container { position: relative; height: 285px; }
    .chart-container-sm { position: relative; height: 210px; }
    .chart-empty {
        min-height: 260px;
        display: grid;
        place-items: center;
        text-align: center;
        border-radius: 16px;
        background: linear-gradient(135deg, #f8fafc 0%, #eef4ff 100%);
        border: 1px dashed #cbd5e1;
        color: #64748b;
    }
    .metric-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
    }
    .metric-row:last-child { border-bottom: 0; }
    .rank-dot {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        font-size: .76rem;
        font-weight: 900;
        flex: 0 0 auto;
    }
    .activity-item {
        display: flex;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #eef2f7;
    }
    .activity-item:last-child { border-bottom: 0; }
    .activity-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
    }
    .empty-state {
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        padding: 18px;
        color: #64748b;
        background: #f8fafc;
    }
    .overview-mobile-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .overview-mobile-tabs::-webkit-scrollbar { display: none; }
    .overview-mobile-tab {
        border: 1px solid #dbe3ef;
        background: #fff;
        color: #334155;
        border-radius: 999px;
        padding: 8px 13px;
        white-space: nowrap;
        font-size: 0.8rem;
        font-weight: 900;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .04);
    }
    .overview-mobile-tab.active {
        background: #0d6efd;
        border-color: #0d6efd;
        color: #fff;
    }
    .overview-mobile-track {
        display: flex;
        overflow-x: auto;
        scroll-snap-type: x mandatory;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    .overview-mobile-track::-webkit-scrollbar { display: none; }
    .overview-mobile-section {
        min-width: 100%;
        scroll-snap-align: start;
        padding-right: 1px;
    }
    @media (max-width: 575.98px) {
        .overview-hero { padding: 14px; border-radius: 16px; }
        .overview-accent { width: 42px; height: 42px; border-radius: 13px; font-size: 1.05rem; }
        .overview-card { border-radius: 15px; }
        .overview-kpi { padding: 13px; }
        .overview-icon { width: 34px; height: 34px; border-radius: 11px; font-size: 1rem; }
        .overview-label { font-size: 0.66rem; }
        .overview-value { font-size: 1.02rem; }
        .chart-container { height: 220px; }
        .chart-container-sm { height: 190px; }
        .chart-empty { min-height: 190px; }
    }
</style>

@php
    $isOperationalDashboard = $isOperationalDashboard ?? false;
    $money = fn ($value) => number_format((float) $value) . ' đ';
    $compactMoney = function ($value) {
        $value = (float) $value;
        if ($value >= 1000000000) {
            return rtrim(rtrim(number_format($value / 1000000000, 1), '0'), '.') . ' tỷ';
        }
        if ($value >= 1000000) {
            return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . ' tr';
        }
        if ($value >= 1000) {
            return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'k';
        }
        return number_format($value);
    };
    $sevenDayTotal = isset($sevenDayRevenue) ? $sevenDayRevenue->sum('revenue') : 0;
@endphp

<div class="overview-shell container-fluid px-1 px-md-2 mb-5">
    <div class="overview-hero mb-3">
        <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="overview-accent"><i class="bi bi-speedometer2"></i></div>
                <div>
                    <div class="text-uppercase text-primary fw-bold small mb-1">Bản tin vận hành</div>
                    <h3 class="overview-title fw-bold text-dark m-0">TỔNG QUAN HỆ THỐNG</h3>
                    <div class="overview-note">Cập nhật tình hình kinh doanh và kho hàng</div>
                </div>
            </div>
            <div class="d-flex align-items-start align-items-md-end flex-column gap-2">
                <span class="badge text-bg-light border px-3 py-2">
                    <i class="bi bi-calendar3 me-1 text-primary"></i>{{ now()->format('d/m/Y') }}
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                    Tháng {{ now()->format('m/Y') }}
                </span>
            </div>
        </div>
    </div>

    @if($isOperationalDashboard)
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-success-subtle text-success"><i class="bi bi-boxes"></i></div>
                        <div><div class="overview-label">Sản phẩm tồn kho</div><div class="overview-value">{{ number_format($totalInStock) }}</div><div class="overview-note mt-1">SN đang ở trạng thái tồn</div></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                        <div><div class="overview-label">Sản phẩm sắp hết</div><div class="overview-value">{{ number_format($lowStockProducts) }}</div><div class="overview-note mt-1">Tồn <= {{ $lowStockThreshold }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="overview-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">Điểm nổi bật kho hàng</h5>
                    <div class="overview-note mb-3">Không hiển thị doanh thu, giá vốn hoặc giá trị tồn kho.</div>
                    <div class="fw-bold small text-uppercase text-danger mb-2">Sắp hết hàng</div>
                    @forelse($lowStockList as $item)
                        <div class="metric-row"><span class="fw-bold text-truncate">{{ $item->product_name }}</span><strong>{{ number_format($item->stock_count) }}</strong></div>
                    @empty
                        <div class="empty-state">Không có nhóm hàng sắp hết.</div>
                    @endforelse
                    <div class="fw-bold small text-uppercase text-primary mt-3 mb-2">Tồn kho nhiều</div>
                    @forelse($highStockProducts as $item)
                        <div class="metric-row"><span class="fw-bold text-truncate">{{ $item->product_name }}</span><strong>{{ number_format($item->stock_count) }}</strong></div>
                    @empty
                        <div class="empty-state">Chưa có dữ liệu tồn kho.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-6">
                <div class="overview-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">Hoạt động gần đây</h5>
                    <div class="overview-note mb-3">Phiếu xuất và nhập kho mới nhất.</div>
                    @forelse($recentVouchers as $voucher)
                        <div class="activity-item"><div class="activity-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></div><div><strong class="text-primary">{{ $voucher->export_code }}</strong><div class="text-muted small">{{ $voucher->buyer_name ?: $voucher->company_name ?: 'N/A' }}</div></div></div>
                    @empty
                    @endforelse
                    @forelse($recentImports as $item)
                        <div class="activity-item"><div class="activity-icon bg-success-subtle text-success"><i class="bi bi-box-arrow-in-down"></i></div><div><strong>{{ $item->productCatalog->product_name ?? 'N/A' }}</strong><div class="text-muted small">{{ $item->supplier->name ?? 'N/A' }} · {{ $item->location->shelf_name ?? 'N/A' }}</div></div></div>
                    @empty
                    @endforelse
                    @if($recentVouchers->isEmpty() && $recentImports->isEmpty())
                        <div class="empty-state">Chưa có hoạt động gần đây.</div>
                    @endif
                </div>
            </div>
        </div>
    @else
    <div class="d-none d-md-block">
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-primary-subtle text-primary"><i class="bi bi-cash-stack"></i></div>
                        <div class="min-w-0">
                            <div class="overview-label">Doanh thu tháng</div>
                            <div class="overview-value">{{ $money($monthlyRevenue) }}</div>
                            <div class="overview-note mt-1">{{ number_format($monthlyOrders) }} phiếu xuất</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-success-subtle text-success"><i class="bi bi-boxes"></i></div>
                        <div class="min-w-0">
                            <div class="overview-label">Sản phẩm tồn kho</div>
                            <div class="overview-value">{{ number_format($totalInStock) }}</div>
                            <div class="overview-note mt-1">SN đang ở trạng thái tồn</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-warning-subtle text-warning"><i class="bi bi-bank"></i></div>
                        <div class="min-w-0">
                            <div class="overview-label">Giá trị tồn kho</div>
                            <div class="overview-value">{{ $money($inventoryValue) }}</div>
                            <div class="overview-note mt-1">Theo giá vốn hiện tại</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="min-w-0">
                            <div class="overview-label">Sản phẩm sắp hết</div>
                            <div class="overview-value">{{ number_format($lowStockProducts) }}</div>
                            <div class="overview-note mt-1">Tồn <= {{ $lowStockThreshold }} sản phẩm</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-stretch mb-3">
            <div class="col-xl-8">
                <div class="overview-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Tín hiệu doanh thu 7 ngày</h5>
                            <div class="overview-note">Tổng 7 ngày: <strong class="text-dark">{{ $money($sevenDayTotal) }}</strong></div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                            <i class="bi bi-graph-up-arrow me-1"></i> 7 ngày
                        </span>
                    </div>
                    @if($hasSevenDayRevenue)
                        <div class="chart-container">
                            <canvas id="dashboardRevenueChart"></canvas>
                        </div>
                    @else
                        <div class="chart-empty">
                            <div>
                                <div class="fs-3 text-primary mb-2"><i class="bi bi-graph-up"></i></div>
                                <div class="fw-bold text-dark">Chưa có doanh thu trong 7 ngày gần nhất</div>
                                <div class="small">Biểu đồ sẽ hiển thị khi có phiếu xuất phát sinh doanh thu.</div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-xl-4">
                <div class="overview-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">Hiệu quả tháng</h5>
                    <div class="overview-note mb-3">Tóm tắt dòng tiền bán hàng</div>
                    <div class="metric-row">
                        <span class="text-muted">Doanh thu hôm nay</span>
                        <strong>{{ $money($todayRevenue) }}</strong>
                    </div>
                    <div class="metric-row">
                        <span class="text-muted">Lãi gộp tháng</span>
                        <strong class="text-success">{{ $money($monthlyGrossProfit) }}</strong>
                    </div>
                    <div class="metric-row">
                        <span class="text-muted">Phiếu xuất tháng</span>
                        <strong>{{ number_format($monthlyOrders) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-5">
                <div class="overview-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Điểm nổi bật kho hàng</h5>
                            <div class="overview-note">Ưu tiên các nhóm hàng cần chú ý</div>
                        </div>
                        <i class="bi bi-box-seam text-primary fs-4"></i>
                    </div>

                    <div class="fw-bold small text-uppercase text-danger mb-2">Sắp hết hàng</div>
                    @forelse($lowStockList as $item)
                        <div class="metric-row py-2">
                            <div class="d-flex align-items-center gap-2 min-w-0">
                                <span class="rank-dot bg-danger-subtle text-danger">{{ $loop->iteration }}</span>
                                <div class="text-truncate">
                                    <div class="fw-bold text-dark text-truncate">{{ $item->product_name }}</div>
                                    <small class="text-muted">{{ $item->supplier_name ?? 'N/A' }}</small>
                                </div>
                            </div>
                            <strong class="text-nowrap">{{ number_format($item->stock_count) }}</strong>
                        </div>
                    @empty
                        <div class="empty-state mb-3">Không có nhóm hàng sắp hết.</div>
                    @endforelse

                    <div class="fw-bold small text-uppercase text-primary mt-3 mb-2">Tồn kho nhiều</div>
                    @forelse($highStockProducts as $item)
                        <div class="metric-row py-2">
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate">{{ $item->product_name }}</div>
                                <small class="text-muted">{{ $item->supplier_name ?? 'N/A' }}</small>
                            </div>
                            <strong class="text-nowrap">{{ number_format($item->stock_count) }}</strong>
                        </div>
                    @empty
                        <div class="empty-state">Chưa có dữ liệu tồn kho.</div>
                    @endforelse
                </div>
            </div>

            <div class="col-xl-3">
                <div class="overview-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">Giá trị cao</h5>
                    <div class="overview-note mb-3">Theo số lượng tồn x giá vốn</div>
                    @forelse($highInventoryValueProducts as $item)
                        <div class="metric-row">
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate">{{ $item->product_name }}</div>
                                <small class="text-muted">Tồn {{ number_format($item->stock_count) }}</small>
                            </div>
                            <strong class="text-nowrap">{{ $compactMoney($item->inventory_value) }}</strong>
                        </div>
                    @empty
                        <div class="empty-state">Chưa có dữ liệu giá trị tồn.</div>
                    @endforelse
                </div>
            </div>

            <div class="col-xl-4">
                <div class="overview-card p-4 h-100">
                    <h5 class="fw-bold text-dark mb-1">Hoạt động gần đây</h5>
                    <div class="overview-note mb-3">Phiếu xuất và nhập kho mới nhất</div>

                    @forelse($recentVouchers as $voucher)
                        <div class="activity-item">
                            <div class="activity-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="text-primary text-truncate">{{ $voucher->export_code }}</strong>
                                    <span class="badge bg-light text-dark border">{{ optional($voucher->exported_at)->format('d/m') }}</span>
                                </div>
                                <div class="text-muted small text-truncate">{{ $voucher->buyer_name ?: $voucher->company_name ?: 'N/A' }}</div>
                                <div class="fw-bold">{{ $money($voucher->total_amount) }}</div>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    @forelse($recentImports as $item)
                        <div class="activity-item">
                            <div class="activity-icon bg-success-subtle text-success"><i class="bi bi-box-arrow-in-down"></i></div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between gap-2">
                                    <strong class="text-dark text-truncate">{{ $item->productCatalog->product_name ?? 'N/A' }}</strong>
                                    <span class="badge bg-light text-dark border">{{ optional($item->created_at)->format('d/m') }}</span>
                                </div>
                                <div class="text-muted small text-truncate">{{ $item->supplier->name ?? 'N/A' }} · {{ $item->location->shelf_name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    @if($recentVouchers->isEmpty() && $recentImports->isEmpty())
                        <div class="empty-state">Chưa có hoạt động gần đây.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="d-md-none">
        <div class="overview-card p-3 mb-3">
            <div class="fw-bold text-dark">Xem nhanh theo mục</div>
            <div class="overview-note">Vuốt ngang hoặc chọn mục để xem nhanh</div>
            <div class="overview-mobile-tabs mt-3">
                <button type="button" class="overview-mobile-tab active" data-overview-index="0">Tổng số</button>
                <button type="button" class="overview-mobile-tab" data-overview-index="1">7 ngày</button>
                <button type="button" class="overview-mobile-tab" data-overview-index="2">Kho hàng</button>
                <button type="button" class="overview-mobile-tab" data-overview-index="3">Gần đây</button>
            </div>
        </div>

        <div class="overview-mobile-track" id="overviewMobileTrack">
            <section class="overview-mobile-section">
                <div class="row g-2">
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-icon bg-primary-subtle text-primary mb-2"><i class="bi bi-cash-stack"></i></div><div class="overview-label">Doanh thu tháng</div><div class="overview-value">{{ $money($monthlyRevenue) }}</div></div></div>
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-icon bg-success-subtle text-success mb-2"><i class="bi bi-boxes"></i></div><div class="overview-label">Tồn kho</div><div class="overview-value">{{ number_format($totalInStock) }}</div></div></div>
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-icon bg-warning-subtle text-warning mb-2"><i class="bi bi-bank"></i></div><div class="overview-label">Giá trị kho</div><div class="overview-value">{{ $money($inventoryValue) }}</div></div></div>
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-icon bg-danger-subtle text-danger mb-2"><i class="bi bi-exclamation-triangle"></i></div><div class="overview-label">Sắp hết</div><div class="overview-value">{{ number_format($lowStockProducts) }}</div></div></div>
                </div>
            </section>

            <section class="overview-mobile-section">
                <div class="overview-card p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="fw-bold text-dark">Tín hiệu doanh thu 7 ngày</div>
                            <div class="overview-note">Tổng: {{ $money($sevenDayTotal) }}</div>
                        </div>
                    </div>
                    @if($hasSevenDayRevenue)
                        <div class="chart-container-sm">
                            <canvas id="dashboardRevenueChartMobile"></canvas>
                        </div>
                    @else
                        <div class="chart-empty">
                            <div>
                                <div class="fs-4 text-primary mb-2"><i class="bi bi-graph-up"></i></div>
                                <div class="fw-bold text-dark">Chưa có doanh thu trong 7 ngày gần nhất</div>
                                <div class="small">Biểu đồ sẽ hiển thị khi có dữ liệu.</div>
                            </div>
                        </div>
                    @endif
                    <div class="metric-row mt-2"><span class="text-muted">Doanh thu hôm nay</span><strong>{{ $money($todayRevenue) }}</strong></div>
                    <div class="metric-row"><span class="text-muted">Lãi gộp tháng</span><strong class="text-success">{{ $money($monthlyGrossProfit) }}</strong></div>
                </div>
            </section>

            <section class="overview-mobile-section">
                <div class="overview-card p-3">
                    <div class="fw-bold text-dark mb-1">Kho hàng</div>
                    <div class="overview-note mb-3">Sản phẩm có tồn kho nhỏ hơn hoặc bằng {{ $lowStockThreshold }} được tính là sắp hết.</div>

                    <div class="fw-bold small text-uppercase text-danger mb-2">Sắp hết</div>
                    @forelse($lowStockList->take(4) as $item)
                        <div class="metric-row">
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate">{{ $item->product_name }}</div>
                                <small class="text-muted">{{ $item->supplier_name ?? 'N/A' }}</small>
                            </div>
                            <strong>{{ number_format($item->stock_count) }}</strong>
                        </div>
                    @empty
                        <div class="empty-state">Không có nhóm hàng sắp hết.</div>
                    @endforelse

                    <div class="fw-bold small text-uppercase text-primary mt-3 mb-2">Giá trị cao</div>
                    @forelse($highInventoryValueProducts->take(4) as $item)
                        <div class="metric-row">
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate">{{ $item->product_name }}</div>
                                <small class="text-muted">Tồn {{ number_format($item->stock_count) }}</small>
                            </div>
                            <strong>{{ $compactMoney($item->inventory_value) }}</strong>
                        </div>
                    @empty
                        <div class="empty-state">Chưa có dữ liệu giá trị tồn.</div>
                    @endforelse
                </div>
            </section>

            <section class="overview-mobile-section">
                <div class="overview-card p-3">
                    <div class="fw-bold text-dark mb-1">Hoạt động gần đây</div>
                    <div class="overview-note mb-2">Phiếu xuất và nhập kho mới nhất</div>

                    @forelse($recentVouchers->take(4) as $voucher)
                        <div class="activity-item">
                            <div class="activity-icon bg-primary-subtle text-primary"><i class="bi bi-receipt"></i></div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-bold text-primary text-truncate">{{ $voucher->export_code }}</div>
                                <div class="text-muted small text-truncate">{{ $voucher->buyer_name ?: $voucher->company_name ?: 'N/A' }}</div>
                                <div class="fw-bold">{{ $money($voucher->total_amount) }}</div>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    @forelse($recentImports->take(4) as $item)
                        <div class="activity-item">
                            <div class="activity-icon bg-success-subtle text-success"><i class="bi bi-box-arrow-in-down"></i></div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-bold text-dark text-truncate">{{ $item->productCatalog->product_name ?? 'N/A' }}</div>
                                <div class="text-muted small text-truncate">{{ $item->supplier->name ?? 'N/A' }} · {{ $item->location->shelf_name ?? 'N/A' }}</div>
                            </div>
                        </div>
                    @empty
                    @endforelse

                    @if($recentVouchers->isEmpty() && $recentImports->isEmpty())
                        <div class="empty-state">Chưa có hoạt động gần đây.</div>
                    @endif
                </div>
            </section>
        </div>
    </div>
    @endif
</div>

@unless($isOperationalDashboard)
<script type="application/json" id="dashboardRevenueChartData">
    {!! json_encode([
        'labels' => $sevenDayChartLabels,
        'values' => $sevenDayChartValues,
        'hasData' => $hasSevenDayRevenue,
    ], JSON_UNESCAPED_UNICODE) !!}
</script>
@endunless
@endsection

@unless($isOperationalDashboard)
@push('scripts')
    @vite(['resources/js/dashboard/overview.js'])
@endpush
@endunless
