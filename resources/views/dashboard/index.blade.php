@extends('layouts.admin')

@section('title', 'Tổng quan')

@section('content')
<style>
    .overview-shell { max-width: 1320px; margin: 0 auto; }
    .overview-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
    }
    .overview-kpi { padding: 16px; height: 100%; }
    .overview-icon {
        width: 42px; height: 42px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
    .overview-label { color: #64748b; font-size: 0.78rem; font-weight: 800; text-transform: uppercase; }
    .overview-value { color: #0f172a; font-size: 1.35rem; font-weight: 800; overflow-wrap: anywhere; }
    .overview-mobile-tabs {
        display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none;
    }
    .overview-mobile-tabs::-webkit-scrollbar { display: none; }
    .overview-mobile-tab {
        border: 1px solid #dbe3ef; background: #f8fafc; color: #334155;
        border-radius: 999px; padding: 7px 12px; white-space: nowrap;
        font-size: 0.8rem; font-weight: 800;
    }
    .overview-mobile-tab.active { background: #0d6efd; border-color: #0d6efd; color: #fff; }
    .overview-mobile-track { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; scrollbar-width: none; -ms-overflow-style: none; }
    .overview-mobile-track::-webkit-scrollbar { display: none; }
    .overview-mobile-section { min-width: 100%; scroll-snap-align: start; padding-right: 1px; }
    @media (max-width: 575.98px) {
        .overview-card { border-radius: 14px; }
        .overview-kpi { padding: 12px; }
        .overview-icon { width: 34px; height: 34px; border-radius: 10px; font-size: 1rem; }
        .overview-label { font-size: 0.68rem; }
        .overview-value { font-size: 1rem; }
        .overview-title { font-size: 1.25rem; }
    }
</style>

@php
    $money = fn ($value) => number_format((float) $value) . ' đ';
@endphp

<div class="overview-shell container-fluid px-1 px-md-2 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <div class="text-uppercase text-primary fw-bold small mb-1">Điều hành kho</div>
            <h3 class="overview-title fw-bold text-dark m-0">TỔNG QUAN</h3>
        </div>
        <div class="text-muted small align-self-md-end">Tháng {{ now()->format('m/Y') }}</div>
    </div>

    <div class="d-none d-md-block">
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-primary-subtle text-primary"><i class="bi bi-cash-stack"></i></div>
                        <div><div class="overview-label">Doanh thu tháng</div><div class="overview-value">{{ $money($monthlyRevenue) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-success-subtle text-success"><i class="bi bi-box-seam"></i></div>
                        <div><div class="overview-label">Sản phẩm tồn kho</div><div class="overview-value">{{ number_format($totalInStock) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-warning-subtle text-warning"><i class="bi bi-bank"></i></div>
                        <div><div class="overview-label">Giá trị tồn kho</div><div class="overview-value">{{ $money($inventoryValue) }}</div></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="overview-card overview-kpi">
                    <div class="d-flex align-items-start gap-3">
                        <div class="overview-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-triangle"></i></div>
                        <div><div class="overview-label">Sản phẩm sắp hết</div><div class="overview-value">{{ number_format($lowStockProducts) }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="overview-card p-3 h-100">
                    <h6 class="fw-bold mb-3">Hiệu quả</h6>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Doanh thu hôm nay</span><strong>{{ $money($todayRevenue) }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Lãi gộp tháng</span><strong>{{ $money($monthlyGrossProfit) }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Phiếu xuất tháng</span><strong>{{ number_format($monthlyOrders) }}</strong></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="overview-card p-3 h-100">
                    <h6 class="fw-bold mb-3">Nhập kho gần đây</h6>
                    @forelse($recentImports as $item)
                        <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate">{{ $item->productCatalog->product_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $item->supplier->name ?? 'N/A' }} · {{ $item->location->shelf_name ?? 'N/A' }}</small>
                            </div>
                            <small class="text-muted text-nowrap">{{ $item->created_at->format('d/m') }}</small>
                        </div>
                    @empty
                        <div class="text-muted py-3">Chưa có dữ liệu nhập kho.</div>
                    @endforelse
                </div>
            </div>
            <div class="col-lg-4">
                <div class="overview-card p-3 h-100">
                    <h6 class="fw-bold mb-3">Hóa đơn gần đây</h6>
                    @forelse($recentVouchers as $voucher)
                        <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                            <div>
                                <div class="fw-bold text-primary">{{ $voucher->export_code }}</div>
                                <small class="text-muted">{{ $voucher->buyer_name ?: $voucher->company_name ?: 'N/A' }}</small>
                            </div>
                            <strong class="text-nowrap">{{ $money($voucher->total_amount) }}</strong>
                        </div>
                    @empty
                        <div class="text-muted py-3">Chưa có hóa đơn.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="d-md-none">
        <div class="overview-mobile-tabs mb-3">
            <button type="button" class="overview-mobile-tab active" data-overview-index="0">Tổng số</button>
            <button type="button" class="overview-mobile-tab" data-overview-index="1">Hiệu quả</button>
            <button type="button" class="overview-mobile-tab" data-overview-index="2">Kho hàng</button>
            <button type="button" class="overview-mobile-tab" data-overview-index="3">Gần đây</button>
        </div>

        <div class="overview-mobile-track" id="overviewMobileTrack">
            <section class="overview-mobile-section">
                <div class="row g-2">
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-label">Doanh thu tháng</div><div class="overview-value">{{ $money($monthlyRevenue) }}</div></div></div>
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-label">Tồn kho</div><div class="overview-value">{{ number_format($totalInStock) }}</div></div></div>
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-label">Giá trị kho</div><div class="overview-value">{{ $money($inventoryValue) }}</div></div></div>
                    <div class="col-6"><div class="overview-card overview-kpi"><div class="overview-label">Sắp hết</div><div class="overview-value">{{ number_format($lowStockProducts) }}</div></div></div>
                </div>
            </section>
            <section class="overview-mobile-section">
                <div class="overview-card p-3">
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Doanh thu hôm nay</span><strong>{{ $money($todayRevenue) }}</strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Lãi gộp tháng</span><strong>{{ $money($monthlyGrossProfit) }}</strong></div>
                    <div class="d-flex justify-content-between py-2"><span class="text-muted">Phiếu xuất tháng</span><strong>{{ number_format($monthlyOrders) }}</strong></div>
                </div>
            </section>
            <section class="overview-mobile-section">
                <div class="overview-card p-3">
                    <div class="fw-bold mb-2">Kho hàng</div>
                    <div class="text-muted small">Sản phẩm có tồn kho nhỏ hơn hoặc bằng {{ $lowStockThreshold }} được tính là sắp hết.</div>
                    <div class="d-flex justify-content-between border-top mt-3 pt-3"><span>Sản phẩm tồn kho</span><strong>{{ number_format($totalInStock) }}</strong></div>
                    <div class="d-flex justify-content-between mt-2"><span>Sản phẩm sắp hết</span><strong>{{ number_format($lowStockProducts) }}</strong></div>
                </div>
            </section>
            <section class="overview-mobile-section">
                <div class="overview-card p-3">
                    <div class="fw-bold mb-2">Hóa đơn gần đây</div>
                    @forelse($recentVouchers as $voucher)
                        <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                            <div class="text-truncate"><div class="fw-bold text-primary">{{ $voucher->export_code }}</div><small class="text-muted">{{ $voucher->buyer_name ?: $voucher->company_name ?: 'N/A' }}</small></div>
                            <strong class="text-nowrap">{{ $money($voucher->total_amount) }}</strong>
                        </div>
                    @empty
                        <div class="text-muted py-3">Chưa có hóa đơn.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const track = document.getElementById('overviewMobileTrack');
    const tabs = document.querySelectorAll('[data-overview-index]');
    if (!track || tabs.length === 0) return;

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const index = Number(this.dataset.overviewIndex || 0);
            track.scrollTo({ left: track.clientWidth * index, behavior: 'smooth' });
        });
    });

    track.addEventListener('scroll', function () {
        const index = Math.round(track.scrollLeft / Math.max(track.clientWidth, 1));
        tabs.forEach((tab, tabIndex) => tab.classList.toggle('active', tabIndex === index));
    }, { passive: true });
});
</script>
@endsection
