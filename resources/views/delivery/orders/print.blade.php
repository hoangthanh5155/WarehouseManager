<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu giao hàng {{ $order->order_code }}</title>
    <style>
        @page { size: A4; margin: 14mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #edf1f5;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.45;
        }
        .preview { width: min(100%, 980px); margin: 0 auto; padding: 18px 12px; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 12px; }
        .btn {
            display: inline-flex; align-items: center; justify-content: center;
            min-height: 38px; padding: 8px 14px; border: 1px solid #cbd5e1;
            border-radius: 6px; background: #fff; color: #334155;
            font-weight: 700; text-decoration: none; cursor: pointer;
        }
        .btn-primary { border-color: #1d4ed8; background: #2563eb; color: #fff; }
        .sheet {
            background: #fff; border: 1px solid #d1d5db; border-radius: 6px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .08); padding: 26px;
        }
        .top { display: grid; grid-template-columns: minmax(0, 1fr) 220px; gap: 18px; border-bottom: 2px solid #111827; padding-bottom: 12px; }
        .company-name { font-size: 18px; font-weight: 800; text-transform: uppercase; }
        .muted { color: #4b5563; }
        .doc-code { border: 1px solid #111827; padding: 10px; text-align: center; font-weight: 700; }
        h1 { margin: 18px 0 14px; text-align: center; font-size: 24px; letter-spacing: 0; }
        .info { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 22px; margin-bottom: 14px; }
        .line { border-bottom: 1px dotted #9ca3af; min-height: 22px; }
        .table-wrap { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; min-width: 760px; border-collapse: collapse; }
        th, td { border: 1px solid #111827; padding: 7px 8px; vertical-align: top; }
        th { background: #f3f4f6; text-align: center; font-weight: 700; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        tfoot th { background: #fff; font-size: 14px; }
        .amount-text { margin-top: 10px; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 34px; text-align: center; }
        .signature-title { font-weight: 700; }
        .signature-note { color: #6b7280; font-style: italic; margin-top: 3px; }
        .signature-space { height: 78px; }
        @media (max-width: 640px) {
            body { background: #fff; }
            .preview { padding: 10px; }
            .toolbar { position: sticky; top: 0; z-index: 2; background: #fff; padding: 8px 0; justify-content: stretch; }
            .toolbar .btn { flex: 1; }
            .sheet { border-radius: 4px; box-shadow: none; padding: 16px; }
            .top, .info, .signatures { grid-template-columns: 1fr; }
            .doc-code { text-align: left; }
            h1 { font-size: 21px; }
            table { min-width: 720px; }
        }
        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .preview { width: 100%; padding: 0; }
            .sheet { border: 0; border-radius: 0; box-shadow: none; padding: 0; }
            .table-wrap { overflow: visible; }
            table { min-width: 0; }
            th, td { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
@php
    $company = $currentCompanyProfile ?? \App\Models\CompanyProfile::current();
    $companyName = $company?->company_name ?: ($systemBrandName ?? \App\Models\CompanyProfile::fallbackName());
@endphp
<div class="preview">
    <div class="toolbar no-print">
        @unless($publicView)
            <a href="{{ route('delivery.orders.index') }}" class="btn">Quay lại</a>
        @endunless
        <button class="btn btn-primary" type="button" onclick="window.print()">In đơn</button>
    </div>

    <div class="sheet">
        <div class="top">
            <div>
                <div class="company-name">{{ $companyName }}</div>
                @if($company?->address)<div>Địa chỉ: {{ $company->address }}</div>@endif
                @if($company?->hotline)<div>Điện thoại: {{ $company->hotline }}</div>@endif
                @if($company?->tax_code)<div>MST: {{ $company->tax_code }}</div>@endif
                @if($company?->bank_account || $company?->bank_name)
                    <div>STK: {{ $company?->bank_account }}{{ $company?->bank_name ? ' - ' . $company->bank_name : '' }}</div>
                @endif
            </div>
            <div class="doc-code">
                <div>Mã phiếu / mã đơn</div>
                <div>{{ $order->order_code }}</div>
                <div class="muted">{{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <h1>PHIẾU GIAO HÀNG</h1>

        <div class="info">
            <div>Khách hàng: <strong>{{ $order->buyer_name ?: '-' }}</strong></div>
            <div>SĐT: <strong>{{ $order->phone ?: '-' }}</strong></div>
            <div style="grid-column: 1 / -1;">Địa chỉ: <strong>{{ $order->address ?: '-' }}</strong></div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width: 42px;">STT</th>
                        <th>Tên sản phẩm</th>
                        <th style="width: 64px;">ĐVT</th>
                        <th style="width: 64px;">SL</th>
                        <th style="width: 110px;">Đơn giá</th>
                        <th style="width: 120px;">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                            <td class="text-center">Cái</td>
                            <td class="text-center">{{ number_format($item->quantity) }}</td>
                            <td class="text-end">{{ number_format($item->unit_price) }} đ</td>
                            <td class="text-end">{{ number_format($item->total_amount) }} đ</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="5" class="text-end">Tổng tiền</th>
                        <th class="text-end">{{ number_format($order->items->sum('total_amount')) }} đ</th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="amount-text">Bằng chữ: <span class="line" style="display:inline-block;width:70%;"></span></div>

        @if($order->note)
            <div style="margin-top:10px;">Ghi chú: {{ $order->note }}</div>
        @endif

        <div class="signatures">
            <div>
                <div class="signature-title">Người mua hàng</div>
                <div class="signature-note">Ký, ghi rõ họ tên</div>
                <div class="signature-space"></div>
            </div>
            <div>
                <div class="signature-title">Người bán hàng</div>
                <div class="signature-note">Ký, ghi rõ họ tên</div>
                <div class="signature-space"></div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
