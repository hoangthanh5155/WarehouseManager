@extends('layouts.admin')

@section('title', 'Tạo đơn xuất hàng')

@section('content')
<div class="container-fluid px-2 mb-5" id="exportPreparePage"
     data-create-order-url="{{ route('export.orders.store') }}"
     data-delivery-orders-url="{{ route('delivery.batches.index') }}">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3 mt-2">
        <div>
            <h4 class="fw-bold text-dark m-0">Tạo đơn xuất hàng</h4>
            <div class="text-muted small">Lập đơn/nhu cầu giao hàng. Serial sẽ được nạp trong chuyến giao.</div>
        </div>
        <span class="badge bg-primary px-3 py-2 fs-6">Hôm nay: {{ date('d/m/Y') }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <ul class="nav nav-pills gap-2 mb-3" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-bold" id="new-order-tab" data-bs-toggle="pill" data-bs-target="#newOrderTab" type="button" role="tab">
                Tạo đơn mới
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="system-order-tab" data-bs-toggle="pill" data-bs-target="#systemOrderTab" type="button" role="tab">
                Đơn hệ thống
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold" id="recent-order-tab" data-bs-toggle="pill" data-bs-target="#recentOrderTab" type="button" role="tab">
                Đơn vừa tạo
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="newOrderTab" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge me-1"></i>Thông tin khách / cửa hàng</h6>

                <div class="mb-3">
                    <label class="small text-muted fw-bold mb-1">Khách hàng</label>
                    <select id="selectCustomer" class="form-select fw-bold border-primary select2">
                        <option value="">Khách mới</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                    data-name="{{ $customer->name }}"
                                    data-company="{{ $customer->company_name }}"
                                    data-address="{{ $customer->address }}"
                                    data-phone="{{ $customer->phone ?? '' }}"
                                    data-tax="{{ $customer->tax_code }}"
                                    data-type="{{ $customer->type }}">
                                {{ $customer->name }} {{ $customer->phone ? '- ' . $customer->phone : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-2">
                    <div class="col-12 col-md-6">
                        <label class="small text-muted fw-bold mb-1">Người mua</label>
                        <input type="text" id="buyerName" class="form-control" placeholder="Tên khách hàng">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="small text-muted fw-bold mb-1">Công ty</label>
                        <input type="text" id="companyName" class="form-control" placeholder="Tên đơn vị">
                    </div>
                    <div class="col-12 col-md-8">
                        <label class="small text-muted fw-bold mb-1">Địa chỉ</label>
                        <input type="text" id="address" class="form-control" placeholder="Địa chỉ giao hàng">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="small text-muted fw-bold mb-1">SĐT</label>
                        <input type="text" id="phone" class="form-control" placeholder="Số điện thoại">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-tags me-1"></i>Loại giá</h6>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="customer_type" id="typeRetail" value="retail" checked autocomplete="off">
                    <label class="btn btn-outline-danger w-50 fw-bold py-2" for="typeRetail">Giá khách lẻ</label>

                    <input type="radio" class="btn-check" name="customer_type" id="typeAgency" value="agency" autocomplete="off">
                    <label class="btn btn-outline-success w-50 fw-bold py-2" for="typeAgency">Giá đại lý</label>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-list-check me-1"></i>Danh sách hàng cần xuất</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="addOrderItem">
                        <i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm
                    </button>
                </div>

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-bordered align-middle m-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center" style="width: 110px;">SL</th>
                                <th class="text-end" style="width: 160px;">Đơn giá</th>
                                <th class="text-end" style="width: 160px;">Thành tiền</th>
                                <th class="text-center" style="width: 70px;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="orderItemsBody"></tbody>
                    </table>
                </div>
                <div id="orderItemsMobile" class="d-md-none vstack gap-3"></div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-5">
                        <div class="p-3 bg-light rounded-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-bold">Tổng tiền</span>
                                <span class="fw-bold text-danger fs-4" id="globalTotalAmount">0 đ</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-7">
                        <div class="d-flex justify-content-end">
                            <button type="button" id="btnSaveAndPrintPrepared" class="btn btn-success fw-bold py-3 px-4 w-100 w-md-auto">
                                <i class="bi bi-printer me-1"></i>Lưu & in đơn
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="systemOrderTab" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clipboard-check me-1"></i>Đơn hệ thống</h6>
                <label class="small text-muted fw-bold mb-1">Chọn đơn</label>
                <select id="systemOrderSelect" class="form-select">
                    <option value="">Chọn đơn đã duyệt</option>
                    @foreach($systemOrders as $order)
                        <option value="{{ $order->id }}">{{ $order->order_code }} - {{ $order->buyer_name }} ({{ number_format($order->total_amount ?? 0) }} đ)</option>
                    @endforeach
                </select>
                <div id="systemOrderInfo" class="mt-3"></div>
                <div class="d-flex justify-content-end mt-3">
                    <button type="button" id="btnPrintSystemOrder" class="btn btn-success fw-bold">
                        <i class="bi bi-printer me-1"></i>In đơn hệ thống
                    </button>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="recentOrderTab" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-1"></i>Đơn vừa tạo</h6>
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle m-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders ?? [] as $order)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $order->order_code }}</td>
                                    <td>{{ $order->company_name ?: $order->buyer_name ?: '-' }}</td>
                                    <td class="text-end fw-bold">{{ number_format($order->total_amount ?? 0) }} đ</td>
                                    <td><span class="badge text-bg-secondary">{{ $order->status }}</span></td>
                                    <td class="text-end">
                                        <a href="{{ route('delivery.orders.print', $order) }}" class="btn btn-sm btn-outline-primary fw-bold">In đơn</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Chưa có đơn vừa tạo.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none d-flex flex-column gap-2">
                    @forelse($recentOrders ?? [] as $order)
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <strong class="text-primary">{{ $order->order_code }}</strong>
                                <span class="fw-bold text-danger">{{ number_format($order->total_amount ?? 0) }} đ</span>
                            </div>
                            <div class="fw-bold text-dark">{{ $order->company_name ?: $order->buyer_name ?: '-' }}</div>
                            <div class="text-muted small mb-2">{{ $order->status }}</div>
                            <a href="{{ route('delivery.orders.print', $order) }}" class="btn btn-sm btn-outline-primary fw-bold w-100">In đơn</a>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">Chưa có đơn vừa tạo.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.exportSystemOrders = @json($systemOrdersPayload ?? []);
    window.exportProductCatalogs = @json($productCatalogsPayload ?? []);
</script>
@endsection

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .btn-check + .btn { font-size: 0.85rem !important; padding: 10px 4px !important; }
        .card { padding: 12px !important; }
        .form-select, .form-control { font-size: 0.9rem !important; }
        .export-order-mobile-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .04);
        }
        .export-order-mobile-card .input-group {
            min-width: 0;
        }
    }
</style>
@endpush

@push('scripts')
    @vite(['resources/js/warehouse/export.js'])
@endpush
