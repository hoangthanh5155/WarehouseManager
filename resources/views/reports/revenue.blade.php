@extends('layouts.admin')

@section('title', 'Bao cao doanh thu')

@section('content')
@php
    $formatMoney = fn ($value) => number_format((float) $value) . ' d';
    $periodLinks = [
        'today' => 'Hom nay',
        '7days' => '7 ngay',
        'month' => 'Thang nay',
        'year' => 'Nam nay',
    ];
@endphp

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <div class="text-uppercase text-primary fw-bold small mb-1">Bao cao - Thong ke</div>
        <h3 class="fw-bold text-dark m-0">Bao cao doanh thu</h3>
        <div class="text-muted small mt-1">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</div>

        <div class="d-flex gap-2 flex-wrap mt-3">
            @foreach($periodLinks as $periodValue => $periodLabel)
                <a href="{{ route('reports.revenue', ['period' => $periodValue]) }}" class="btn btn-sm {{ $period === $periodValue ? 'btn-primary' : 'btn-outline-primary' }} fw-bold">{{ $periodLabel }}</a>
            @endforeach
            <a href="{{ route('reports.revenue', ['period' => 'custom', 'start_date' => $startDate->toDateString(), 'end_date' => $endDate->toDateString()]) }}" class="btn btn-sm {{ $period === 'custom' ? 'btn-primary' : 'btn-outline-primary' }} fw-bold">Tu - den</a>
        </div>

        <form action="{{ route('reports.revenue') }}" method="GET" class="row g-2 align-items-end mt-3 {{ $period === 'custom' ? '' : 'd-none' }}">
            <input type="hidden" name="period" value="custom">
            <div class="col-6 col-md-3">
                <label class="small text-muted fw-bold mb-1">Tu ngay</label>
                <input type="date" name="start_date" value="{{ $startDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-6 col-md-3">
                <label class="small text-muted fw-bold mb-1">Den ngay</label>
                <input type="date" name="end_date" value="{{ $endDate->toDateString() }}" class="form-control">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary fw-bold px-4 w-100"><i class="bi bi-funnel me-1"></i>Loc</button>
            </div>
        </form>
    </div>

    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Doanh thu</div><div class="fs-4 fw-bold text-primary">{{ $formatMoney($totalRevenue) }}</div></div></div>
        @if($canViewCost)
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Gia von</div><div class="fs-4 fw-bold">{{ $formatMoney($totalCost) }}</div></div></div>
            <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Loi nhuan gop</div><div class="fs-4 fw-bold {{ $grossProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $formatMoney($grossProfit) }}</div></div></div>
        @endif
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">So phieu xuat</div><div class="fs-4 fw-bold">{{ number_format($exportOrderCount) }}</div></div></div>
        <div class="col-6 col-lg-3"><div class="bg-white shadow-sm rounded-4 p-3 h-100"><div class="text-muted small fw-bold">Serial da xuat</div><div class="fs-4 fw-bold">{{ number_format($exportedSerialCount ?: $exportedProductCount) }}</div></div></div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark m-0">Phieu xuat trong ky</h5>
                <span class="badge bg-light text-dark border">{{ number_format($vouchers->total()) }} phieu</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ma phieu</th>
                            <th>Khach hang</th>
                            <th>Loai khach</th>
                            <th class="text-end">Serial</th>
                            <th class="text-end">Doanh thu</th>
                            @if($canViewCost)
                                <th class="text-end">Gia von</th>
                                <th class="text-end">Lai gop</th>
                            @endif
                            <th>Ngay xuat</th>
                            <th class="text-end">Thao tac</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vouchers as $voucher)
                            @php
                                $voucherAmount = (float) ($voucher->item_total_amount ?? $voucher->total_amount);
                                $voucherCost = (float) ($voucher->item_total_cost ?? $voucher->total_cost);
                                $voucherProfit = $voucherAmount - $voucherCost;
                                $customerName = $voucher->company_name ?: $voucher->buyer_name;
                            @endphp
                            <tr>
                                <td class="fw-bold text-primary">{{ $voucher->export_code }}</td>
                                <td>{{ $customerName ?: 'N/A' }}</td>
                                <td><span class="badge {{ $voucher->customer_type === 'agency' ? 'bg-success' : 'bg-danger' }}">{{ $voucher->customer_type === 'agency' ? 'Dai ly' : 'Khach le' }}</span></td>
                                <td class="text-end">{{ number_format($voucher->item_quantity ?? 0) }}</td>
                                <td class="text-end fw-bold">{{ $formatMoney($voucherAmount) }}</td>
                                @if($canViewCost)
                                    <td class="text-end text-muted">{{ $formatMoney($voucherCost) }}</td>
                                    <td class="text-end fw-bold {{ $voucherProfit >= 0 ? 'text-success' : 'text-danger' }}">{{ $formatMoney($voucherProfit) }}</td>
                                @endif
                                <td class="text-nowrap">{{ optional($voucher->exported_at)->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('export.print', $voucher->id) }}" class="btn btn-sm btn-outline-primary fw-bold"><i class="bi bi-eye me-1"></i>Xem hoa don</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $canViewCost ? 9 : 7 }}" class="text-center text-muted py-4">Khong co phieu xuat trong khoang thoi gian nay.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $vouchers->links() }}</div>
        </div>
    </div>
</div>
@endsection
