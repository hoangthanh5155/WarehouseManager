@extends('layouts.admin')

@section('title', 'Xuất kho')

@section('content')
<div class="container-fluid px-2 mb-5" id="exportPreparePage" data-delivery-orders-url="{{ route('delivery.orders.index') }}">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3 mt-2">
        <div>
            <h4 class="fw-bold text-dark m-0">Xuất kho</h4>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
            <button class="btn btn-sm btn-outline-primary fw-bold" type="button" id="recentInvoicesToggle" aria-pressed="false">
                <i class="bi bi-receipt me-1"></i>
                <span data-recent-toggle-label>Hóa đơn gần đây</span>
            </button>
            <span class="badge bg-primary px-3 py-2 fs-6">Hôm nay: {{ date('d/m/Y') }}</span>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="tab-content" id="exportPageTabsContent">
        <div class="tab-pane fade" id="recentInvoicesTab" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-3 p-3 mb-0 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark m-0"><i class="bi bi-receipt me-1"></i> Hóa đơn gần đây</h6>
                </div>

                <div class="table-responsive d-none d-md-block">
                    <table class="table table-hover align-middle m-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Mã hóa đơn</th>
                                <th>Khách hàng</th>
                                <th class="text-end">Tổng tiền</th>
                                <th>Ngày xuất</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentVouchers ?? [] as $recentVoucher)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $recentVoucher->export_code }}</td>
                                    <td>
                                        <div class="fw-bold">{{ $recentVoucher->company_name ?: $recentVoucher->buyer_name ?: 'N/A' }}</div>
                                        @if($recentVoucher->company_name && $recentVoucher->buyer_name)
                                            <small class="text-muted">{{ $recentVoucher->buyer_name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold">{{ number_format($recentVoucher->total_amount) }} đ</td>
                                    <td class="text-nowrap">{{ optional($recentVoucher->exported_at)->format('d/m/Y H:i') }}</td>
                                    <td class="text-end text-nowrap">
                                        <a href="{{ route('export.print', $recentVoucher->id) }}" class="btn btn-sm btn-outline-primary fw-bold">Xem</a>
                                        @if(auth()->user()?->canEditExportMetadata())
                                            <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-bs-toggle="modal" data-bs-target="#editVoucherModal{{ $recentVoucher->id }}">Sửa</button>
                                        @endif
                                        <a href="{{ route('export.print', ['id' => $recentVoucher->id, 'print' => 1]) }}" class="btn btn-sm btn-primary fw-bold">In lại</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Chưa có hóa đơn gần đây.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="d-md-none d-flex flex-column gap-2">
                    @forelse($recentVouchers ?? [] as $recentVoucher)
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <strong class="text-primary">{{ $recentVoucher->export_code }}</strong>
                                <span class="fw-bold text-danger">{{ number_format($recentVoucher->total_amount) }} đ</span>
                            </div>
                            <div class="fw-bold text-dark">{{ $recentVoucher->company_name ?: $recentVoucher->buyer_name ?: 'N/A' }}</div>
                            <div class="text-muted small mb-2">{{ optional($recentVoucher->exported_at)->format('d/m/Y H:i') }}</div>
                            <div class="d-flex gap-2">
                                <a href="{{ route('export.print', $recentVoucher->id) }}" class="btn btn-sm btn-outline-primary fw-bold flex-fill">Xem</a>
                                <a href="{{ route('export.print', ['id' => $recentVoucher->id, 'print' => 1]) }}" class="btn btn-sm btn-primary fw-bold flex-fill">In lại</a>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3">Chưa có hóa đơn gần đây.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="tab-pane fade show active" id="exportWorkflowTab" role="tabpanel" tabindex="0">
            <div class="card border-0 shadow-sm rounded-3 p-3 mb-3 bg-white">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="small text-muted fw-bold mb-1">Kiểu xuất hàng</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="export_type" id="exportNormal" value="normal" checked autocomplete="off">
                            <label class="btn btn-outline-primary w-50 fw-bold py-2" for="exportNormal">
                                <i class="bi bi-upc-scan me-1"></i>Xuất thường
                            </label>

                            <input type="radio" class="btn-check" name="export_type" id="exportSystem" value="system" autocomplete="off">
                            <label class="btn btn-outline-secondary w-50 fw-bold py-2" for="exportSystem">
                                <i class="bi bi-clipboard-check me-1"></i>Đơn hệ thống
                            </label>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="small text-muted fw-bold mb-1">Áp dụng mức giá</label>
                        <div class="d-flex gap-2">
                            <input type="radio" class="btn-check" name="customer_type" id="typeRetail" value="retail" checked autocomplete="off">
                            <label class="btn btn-outline-danger w-50 fw-bold py-2" for="typeRetail">
                                <i class="bi bi-person me-1"></i>Giá khách lẻ
                            </label>

                            <input type="radio" class="btn-check" name="customer_type" id="typeAgency" value="agency" autocomplete="off">
                            <label class="btn btn-outline-success w-50 fw-bold py-2" for="typeAgency">
                                <i class="bi bi-shop me-1"></i>Giá đại lý
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3" id="normalCustomerPanel">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge me-1"></i>Thông tin người mua</h6>

                <div class="mb-3">
                    <label class="small text-muted fw-bold mb-1">Khách hàng</label>
                    <select id="selectCustomer" class="form-select fw-bold border-primary select2">
                        <option value="">Khách mới</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                    data-name="{{ $customer->name }}"
                                    data-company="{{ $customer->company_name }}"
                                    data-address="{{ $customer->address }}"
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
                        <input type="text" id="taxCode" class="form-control" placeholder="Số điện thoại">
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3 d-none" id="systemOrderPanel">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clipboard-check me-1"></i>Đơn hệ thống</h6>
                <label class="small text-muted fw-bold mb-1">Chọn đơn</label>
                <select id="systemOrderSelect" class="form-select">
                    <option value="">Chọn đơn đã duyệt</option>
                    @foreach($systemOrders as $order)
                        <option value="{{ $order->id }}">{{ $order->order_code }} - {{ $order->buyer_name }} ({{ number_format($order->total_amount ?? 0) }} đ)</option>
                    @endforeach
                </select>
                <div id="systemOrderInfo" class="mt-3"></div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <h6 class="fw-bold text-primary m-0"><i class="bi bi-upc-scan me-1"></i>Quét SN</h6>
                    <span class="text-muted small" id="scanSummary">0 SN</span>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="serialScanInput" class="form-control form-control-lg" placeholder="Quét hoặc nhập SN" autocomplete="off" autofocus>
                    <button type="button" id="btnAddSerial" class="btn btn-primary fw-bold">Thêm</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle m-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Tên sản phẩm</th>
                                <th class="text-center" style="width: 90px;">SL</th>
                                <th>SN đã quét</th>
                                <th class="text-end" style="width: 140px;">Giá bán</th>
                                <th class="text-end" style="width: 140px;">Thành tiền</th>
                                <th class="text-center" style="width: 60px;">Xóa</th>
                            </tr>
                        </thead>
                        <tbody id="preparedItemsBody">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Chưa có SN.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
                        <div class="d-flex flex-column flex-md-row gap-2 justify-content-end">
                            <button type="button" id="btnSavePrepared" class="btn btn-outline-success fw-bold py-3 px-4">
                                <i class="bi bi-save me-1"></i>Lưu chờ giao
                            </button>
                            <button type="button" id="btnSaveAndPrintPrepared" class="btn btn-success fw-bold py-3 px-4">
                                <i class="bi bi-printer me-1"></i>Lưu & in đơn
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(auth()->user()?->canEditExportMetadata())
@foreach($recentVouchers ?? [] as $recentVoucher)
    <div class="modal fade" id="editVoucherModal{{ $recentVoucher->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('export.metadata.update', $recentVoucher) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold">Sửa thông tin khách</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Người mua</label>
                                <input type="text" name="buyer_name" value="{{ $recentVoucher->buyer_name }}" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="small text-muted fw-bold mb-1">Công ty</label>
                                <input type="text" name="company_name" value="{{ $recentVoucher->company_name }}" class="form-control">
                            </div>
                            <div class="col-md-8">
                                <label class="small text-muted fw-bold mb-1">Địa chỉ khách</label>
                                <input type="text" name="address" value="{{ $recentVoucher->address }}" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted fw-bold mb-1">SĐT</label>
                                <input type="text" name="tax_code" value="{{ $recentVoucher->tax_code }}" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-primary fw-bold">Lưu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endif

<script>
    window.exportSystemOrders = @json($systemOrdersPayload ?? []);
</script>
@endsection

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .btn-check + .btn { font-size: 0.85rem !important; padding: 10px 4px !important; }
        .card { padding: 12px !important; }
        .form-select, .form-control { font-size: 0.9rem !important; }
    }
</style>
@endpush

@push('scripts')
    @vite(['resources/js/warehouse/export.js'])
@endpush
