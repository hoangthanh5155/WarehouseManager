<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Phiếu giao hàng {{ $order->order_code }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #eef2f7;
            color: #172033;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }
        .container {
            width: min(100%, 980px);
            margin: 0 auto;
            padding: 20px 12px;
        }
        .toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-bottom: 12px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #fff;
            color: #334155;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-primary {
            border-color: #1d4ed8;
            background: #2563eb;
            color: #fff;
        }
        .sheet {
            overflow: hidden;
            border: 1px solid #dbe3ef;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
        }
        .sheet-body { padding: 28px; }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
        }
        h1 {
            margin: 0 0 4px;
            font-size: 28px;
            line-height: 1.2;
        }
        .muted { color: #64748b; }
        .small { font-size: 12px; }
        .fw-bold { font-weight: 700; }
        .fw-semibold { font-weight: 600; }
        .text-end { text-align: right; }
        .text-md-end { text-align: right; }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .table-wrap {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            min-width: 680px;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #dbe3ef;
            padding: 10px;
            vertical-align: top;
        }
        th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }
        tfoot th {
            background: #f1f5f9;
            font-size: 15px;
        }
        .serial-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            min-width: 180px;
        }
        .badge {
            display: inline-flex;
            max-width: 100%;
            padding: 3px 7px;
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #f8fafc;
            color: #172033;
            font-size: 12px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
        .note {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 640px) {
            body { background: #fff; }
            .container { padding: 12px; }
            .toolbar {
                position: sticky;
                top: 0;
                z-index: 2;
                justify-content: stretch;
                padding: 8px 0;
                background: #fff;
            }
            .toolbar .btn { flex: 1; }
            .sheet {
                border-radius: 6px;
                box-shadow: none;
            }
            .sheet-body { padding: 18px 14px; }
            .header {
                flex-direction: column;
                margin-bottom: 18px;
            }
            h1 { font-size: 24px; }
            .text-md-end { text-align: left; }
            .info-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            table { min-width: 620px; }
        }

        @media print {
            body {
                background: #fff;
                color: #000;
            }
            .no-print { display: none !important; }
            .container {
                width: 100%;
                max-width: none;
                padding: 0;
            }
            .sheet {
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
            .sheet-body { padding: 0; }
            .table-wrap { overflow: visible; }
            table { min-width: 0; }
            th, td { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
@php
    $statusLabel = [
        'ready_to_deliver' => 'Chờ giao',
        'in_delivery' => 'Đang giao',
        'delivered' => 'Đã giao',
        'failed' => 'Giao thất bại',
        'cancelled' => 'Đã hủy',
    ];
    $serials = $order->preparedSerials;
@endphp
<div class="container">
    <div class="toolbar no-print">
        @unless($publicView)
            <a href="{{ route('delivery.orders.index') }}" class="btn">Quay lại</a>
        @endunless
        <button class="btn btn-primary" type="button" onclick="window.print()">In đơn</button>
    </div>

    <div class="sheet">
        <div class="sheet-body">
            <div class="header">
                <div>
                    <h1>Phiếu giao hàng</h1>
                    <div class="muted">{{ $order->order_code }}</div>
                </div>
                <div class="text-md-end">
                    <div class="fw-semibold">{{ $statusLabel[$order->status] ?? $order->status }}</div>
                    <div class="muted small">Ngày tạo: {{ optional($order->created_at)->format('d/m/Y H:i') }}</div>
                    @if($order->delivered_at)
                        <div class="muted small">Ngày giao: {{ optional($order->delivered_at)->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
            </div>

            <div class="info-grid">
                <div>
                    <div class="muted small">Khách hàng</div>
                    <div class="fw-bold">{{ $order->buyer_name }}</div>
                    @if($order->company_name)
                        <div>{{ $order->company_name }}</div>
                    @endif
                </div>
                <div>
                    <div class="muted small">Địa chỉ</div>
                    <div>{{ $order->address ?: '-' }}</div>
                    @if($order->tax_code)
                        <div class="muted small">SĐT: {{ $order->tax_code }}</div>
                    @endif
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th class="text-end">SL</th>
                            <th>Serial</th>
                            <th class="text-end">Đơn giá</th>
                            <th class="text-end">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            @php($itemSerials = $serials->where('fulfillment_order_item_id', $item->id))
                            <tr>
                                <td class="fw-semibold">{{ $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A') }}</td>
                                <td class="text-end">{{ number_format($item->quantity) }}</td>
                                <td>
                                    <div class="serial-list">
                                        @foreach($itemSerials as $serial)
                                            <span class="badge">{{ $serial->serial_number_snapshot }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-end">{{ number_format($item->unit_price) }} đ</td>
                                <td class="text-end fw-bold">{{ number_format($item->total_amount) }} đ</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Tổng tiền</th>
                            <th class="text-end">{{ number_format($order->items->sum('total_amount')) }} đ</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($order->note)
                <div class="note">
                    <div class="muted small">Ghi chú</div>
                    <div>{{ $order->note }}</div>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
