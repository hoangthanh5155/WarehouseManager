<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In Hóa Đơn Xuất Kho</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        /* CSS cho preview trên màn hình (Hỗ trợ cả Desktop & Mobile) */
        body {
            background: #f4f6f9;
            font-family: 'Times New Roman', Times, serif;
            margin: 0;
            padding: 0;
        }

        /* Vùng bọc ngoài trang in để cuộn ngang trên điện thoại nếu cần */
        .invoice-preview-container {
            width: 100%;
            overflow-x: auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        page {
            background: white;
            display: block;
            margin: 10px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            box-sizing: border-box;
            word-wrap: break-word;
        }

        /* Chế độ xem trên màn hình (Preview) */
        page[size="A4"] {  
            width: 21cm;
            min-height: 29.7cm; 
            padding: 1.5cm 1.2cm; /* Giảm nhẹ lề để nội dung không bị tràn */
        }
        
        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-top: 15px;
            margin-bottom: 20px;
            letter-spacing: 0.5px;
        }

        /* Kẻ bảng sắc nét */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px; /* Giảm nhẹ cỡ chữ để vừa khung mobile */
        }

        .table-custom th, .table-custom td {
            border: 1px solid #000 !important;
            vertical-align: middle;
            padding: 6px 8px;
        }

        .table-custom thead {
            background-color: #f8f9fa !important;
        }

        .signature-box {
            margin-top: 25px;
            text-align: center;
            font-size: 13px;
        }

        .signature-box strong {
            display: block;
            margin-bottom: 65px; /* Khoảng trống ký tên vừa đủ */
        }

        .page-break {
            page-break-before: always;
        }

        /* 📱 Responsive CSS: Giúp xem trên điện thoại không bị vỡ */
        @media screen and (max-width: 768px) {
            .invoice-preview-container {
                padding: 5px;
                align-items: start; /* Cho phép cuộn ngang tự nhiên */
            }
            page[size="A4"] {
                width: 21cm; /* Giữ nguyên kích thước A4 chuẩn */
                margin: 0;
                transform: scale(0.45); /* Thu nhỏ tỉ lệ hiển thị cho vừa màn hình điện thoại */
                transform-origin: top left;
                box-shadow: none;
                margin-bottom: -15.5cm; /* Bù trừ khoảng trống do lệnh scale */
            }
            .no-print {
                position: sticky;
                top: 0;
                z-index: 9999;
                background: #f4f6f9;
                padding: 10px 0;
                width: 100%;
            }
            .no-print .btn {
                font-size: 14px;
                padding: 8px 14px;
            }
        }

        /* 🖨️ CSS chuẩn chỉnh dành riêng cho máy in */
        @media print {
            body {
                background: white;
            }
            .invoice-preview-container {
                padding: 0;
                overflow: visible;
            }
            page[size="A4"] {
                width: 21cm;
                min-height: 29.7cm;
                margin: 0;
                padding: 1.5cm 1.2cm;
                box-shadow: none;
                transform: none !important; /* Bỏ scale khi in */
            }
            .no-print {
                display: none !important;
            }
            .table-custom th, .table-custom td {
                border: 1px solid #000 !important;
            }
        }
    </style>

    @vite(['resources/js/pages/print_invoice.js'])
</head>
<body>

    <div class="text-center my-2 no-print">
        <button id="btn-print" class="btn btn-primary btn-md shadow-sm fw-bold">
            🖨️ In lại Hóa đơn
        </button>
        <button id="btn-back" class="btn btn-secondary btn-md shadow-sm ms-2 fw-bold">
            ⬅️ Quay lại
        </button>
    </div>

    @php
        $allVouchers = collect([$voucher]);
        if (isset($subVouchers) && $subVouchers->isNotEmpty()) {
            $allVouchers = $allVouchers->merge($subVouchers);
        }
    @endphp

    <div class="invoice-preview-container">
        @foreach($allVouchers as $index => $v)
            @php
                $sellerName = $v->seller_name ?: config('app.name', 'WMS');
                $sellerAddress = $v->seller_address ?: '..................................................................';
                $sellerPhone = $v->seller_phone ?: '................................';
                $sellerTaxCode = $v->seller_tax_code ?: '................................';
            @endphp
            <page size="A4" class="{{ $index > 0 ? 'page-break' : '' }}">
                
                <div class="row" style="font-size: 13px;">
                    <div class="col-8">
                        <h6 class="fw-bold mb-1" style="font-size: 14px;">{{ $sellerName }}</h6>
                        <p class="mb-1"><strong>Địa chỉ:</strong> {{ $sellerAddress }}</p>
                        <p class="mb-1"><strong>Điện thoại:</strong> {{ $sellerPhone }} - <strong>MST:</strong> {{ $sellerTaxCode }}</p>
                    </div>
                    <div class="col-4 text-end">
                        <p class="mb-1">Số: <strong>{{ $v->export_code ?? $v->id }}</strong></p>
                        <p class="mb-1">Ngày: {{ \Carbon\Carbon::parse($v->exported_at ?? $v->created_at)->format('d/m/Y') }}</p>
                    </div>
                </div>

                <div class="invoice-title">
                    HÓA ĐƠN XUẤT KHO / BÁN HÀNG
                </div>

                <div class="mb-3" style="font-size: 13px; line-height: 1.6;">
                    <div class="row">
                        <div class="col-12"><p class="mb-1"><strong>Khách hàng:</strong> {{ $v->buyer_name ?? '..................................................................' }}</p></div>
                        <div class="col-12"><p class="mb-1"><strong>Đơn vị:</strong> {{ $v->company_name ?? '..................................................................' }}</p></div>
                        <div class="col-12"><p class="mb-1"><strong>Địa chỉ:</strong> {{ $v->address ?? '..................................................................' }}</p></div>
                        <div class="col-12"><p class="mb-1"><strong>SĐT / MST:</strong> {{ $v->tax_code ?? '................................................' }}</p></div>
                    </div>
                </div>

                <table class="table-custom w-100">
                    <thead>
                        <tr class="text-center">
                            <th style="width: 6%;">STT</th>
                            <th style="width: 48%;">Tên hàng hóa, dịch vụ</th>
                            <th style="width: 10%;">SL</th>
                            <th style="width: 16%;">Đơn giá (đ)</th>
                            <th style="width: 20%;">Thành tiền (đ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $items = is_string($v->items) ? json_decode($v->items, true) : $v->items;
                            $stt = 1;
                        @endphp

                        @if(!empty($items) && is_array($items))
                            @foreach($items as $item)
                                <tr>
                                    <td class="text-center">{{ $stt++ }}</td>
                                    <td>{{ $item['product_name'] ?? 'N/A' }}</td>
                                    <td class="text-center fw-bold">{{ $item['quantity'] ?? 0 }}</td>
                                    <td class="text-end">{{ number_format($item['price'] ?? 0) }}</td>
                                    <td class="text-end">{{ number_format(($item['quantity'] ?? 0) * ($item['price'] ?? 0)) }}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5" class="text-center">Không có dữ liệu hàng hóa</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Tổng cộng thành tiền:</th>
                            <th class="text-end text-danger" style="font-size: 14px;">{{ number_format($v->total_amount ?? 0) }}</th>
                        </tr>
                    </tfoot>
                </table>

                <div class="row signature-box">
                    <div class="col-4">
                        <strong>Người lập phiếu</strong>
                        <span>(Ký, ghi rõ họ tên)</span>
                    </div>
                    <div class="col-4">
                        <strong>Người giao hàng</strong>
                        <span>(Ký, ghi rõ họ tên)</span>
                    </div>
                    <div class="col-4">
                        <strong>Người mua hàng</strong>
                        <span>(Ký, ghi rõ họ tên)</span>
                    </div>
                </div>

            </page>
        @endforeach
    </div>

</body>
</html>
