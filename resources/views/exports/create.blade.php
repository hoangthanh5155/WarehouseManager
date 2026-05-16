@extends('layouts.admin')

@section('title', 'Xuất kho - POS BanHang')

@section('content')
<div class="container-fluid px-2 mb-5">
    <div class="d-flex justify-content-between align-items-start gap-2 mb-3 mt-2">
        <div>
            <h4 class="fw-bold text-dark m-0">XUẤT KHO</h4>
            <div class="text-muted small">Tạo phiếu xuất kho và in hóa đơn</div>
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
                                <a href="{{ route('export.print', $recentVoucher->id) }}" class="btn btn-sm btn-outline-primary fw-bold">Xem hóa đơn</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-bs-toggle="modal" data-bs-target="#editVoucherModal{{ $recentVoucher->id }}">Sửa thông tin khách</button>
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
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold flex-fill" data-bs-toggle="modal" data-bs-target="#editVoucherModal{{ $recentVoucher->id }}">Sửa</button>
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
                <label class="small text-muted fw-bold mb-1">Kiểu xuất hàng:</label>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="export_type" id="exportNormal" value="normal" checked autocomplete="off">
                    <label class="btn btn-outline-primary w-50 fw-bold py-2" for="exportNormal">🛒 Xuất thường</label>

                    <input type="radio" class="btn-check" name="export_type" id="exportSystem" value="system" autocomplete="off">
                    <label class="btn btn-outline-secondary w-50 fw-bold py-2" for="exportSystem">📋 Đơn hệ thống</label>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <label class="small text-muted fw-bold mb-1">Áp dụng mức giá:</label>
                <div class="d-flex gap-2">
                    <input type="radio" class="btn-check" name="customer_type" id="typeRetail" value="retail" checked autocomplete="off">
                    <label class="btn btn-outline-danger w-50 fw-bold py-2" for="typeRetail">🙋 Giá Khách lẻ</label>

                    <input type="radio" class="btn-check" name="customer_type" id="typeAgency" value="agency" autocomplete="off">
                    <label class="btn btn-outline-success w-50 fw-bold py-2" for="typeAgency">🏪 Giá Đại lý</label>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3">
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-person-badge me-1"></i> THÔNG TIN NGƯỜI MUA HÀNG</h6>

        <div class="mb-3">
            <label class="small text-muted fw-bold mb-1">Tìm khách hàng cũ (Nếu có):</label>
            <select id="selectCustomer" class="form-select fw-bold border-primary select2">
                <option value="">-- Khách mới (Tự nhập tay bên dưới) --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" 
                            data-name="{{ $customer->name }}" 
                            data-company="{{ $customer->company_name }}" 
                            data-address="{{ $customer->address }}" 
                            data-tax="{{ $customer->tax_code }}"
                            data-type="{{ $customer->type }}">
                        {{ $customer->name }} {{ $customer->phone ? '- ' . $customer->phone : '' }} ({{ $customer->company_name ?: 'Khách lẻ' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="row g-2">
            <div class="col-12 col-md-6">
                <label class="small text-muted fw-bold mb-1">Người mua hàng:</label>
                <input type="text" id="buyerName" class="form-control" placeholder="Tên khách hàng ">
            </div>

            <div class="col-12 col-md-6">
                <label class="small text-muted fw-bold mb-1">Tên đơn vị (nếu có):</label>
                <input type="text" id="companyName" class="form-control" placeholder="Tên tổ chức ">
            </div>

            <div class="col-12 col-md-8">
                <label class="small text-muted fw-bold mb-1">Địa chỉ:</label>
                <input type="text" id="address" class="form-control" placeholder="Địa chỉ giao hàng">
            </div>

            <div class="col-12 col-md-4">
                <label class="small text-muted fw-bold mb-1">SĐT:</label>
                <input type="text" id="taxCode" class="form-control" placeholder="Số điện thoại khách hàng">
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3" id="mainExportTable" style="border-left: 4px solid #0d6efd !important;">
        <h6 class="fw-bold text-primary mb-3"><i class="bi bi-cart-plus me-1"></i> SẢN PHẨM ĐƠN CHÍNH</h6>

        <div class="row g-2 align-items-end mb-3">
            <div class="col-12 col-md-6">
                <label class="small text-muted fw-bold mb-1">Tìm tên hàng hóa:</label>
                <select id="selectProductMain" class="form-select select2">
                    <option value="">-- Chọn sản phẩm trong kho --</option>
                    @foreach($productCatalogs as $product)
                        @php
                            $stock = $product->products_count ?? $product->total_qty ?? 0;
                        @endphp
                        <option value="{{ $product->id }}" 
                                data-name="{{ $product->product_name }}" 
                                data-retail="{{ $product->retail_price }}" 
                                data-agency="{{ $product->agency_price }}">
                            {{ $product->product_name }} (Kho: {{ $stock }} cái)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="small text-muted fw-bold mb-1">Số lượng:</label>
                <input type="number" id="inputQtyMain" class="form-control" value="1" min="1">
            </div>
            <div class="col-6 col-md-3">
                <button type="button" id="btnAddProductMain" class="btn btn-primary w-100 fw-bold">
                    <i class="bi bi-plus-circle me-1"></i> Thêm vào đơn
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle m-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>Tên hàng hóa, dịch vụ</th>
                        <th class="text-center" style="width: 100px;">SL</th>
                        <th class="text-center" style="width: 120px;">Giá bán</th>
                        <th class="text-center" style="width: 50px;">Xóa</th>
                    </tr>
                </thead>
                <tbody id="mainExportItems">
                    <tr class="empty-row-main">
                        <td colspan="4" class="text-center py-4 text-muted">📭 Chưa có sản phẩm nào cho đơn chính.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white mb-3" style="border-left: 4px solid #ffc107 !important;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-dark m-0"><i class="bi bi-file-earmark-plus me-1"></i> ĐƠN HÀNG MỞ RỘNG (IN RIÊNG)</h6>
            <button type="button" id="btnCreateSubVoucher" class="btn btn-sm btn-outline-warning fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Thêm đơn mở rộng
            </button>
        </div>

        <div id="subVouchersContainer"></div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-6">
                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted fw-bold">Tổng tiền tất cả các đơn:</span>
                        <span class="fw-bold text-danger fs-4" id="globalTotalAmount">0 đ</span>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <button type="button" id="btnOpenVerifyModal" class="btn btn-success w-100 fw-bold py-3 shadow fs-5">
                    <i class="bi bi-shield-check me-2"></i> XÁC NHẬN ĐƠN HÀNG
                </button>
            </div>
        </div>
    </div>
    </div>
    </div>
</div>

@foreach($recentVouchers ?? [] as $recentVoucher)
    <div class="modal fade" id="editVoucherModal{{ $recentVoucher->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('export.metadata.update', $recentVoucher) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title fw-bold">Sửa thông tin khách</h5>
                            <div class="text-muted small">{{ $recentVoucher->export_code }} - chỉ sửa thông tin người mua, không sửa hàng hóa hoặc tồn kho.</div>
                        </div>
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
                        <button type="submit" class="btn btn-primary fw-bold">Lưu thông tin khách</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="verifySnModal" tabindex="-1" aria-labelledby="verifySnModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold" id="verifySnModalLabel">
                    <i class="bi bi-qr-code-scan me-2"></i>Xác minh mã SN trước khi xuất kho
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted mb-3">
                    Vui lòng quét hoặc nhập chính xác mã Serial Number (SN) của từng sản phẩm trong đơn để đảm bảo xuất đúng hàng tồn kho.
                </p>
                <div id="verifyListArea" class="mb-3" style="max-height: 380px; overflow-y: auto;">
                    </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary px-4 fw-bold" data-bs-dismiss="modal">Hủy</button>
                <button type="button" id="btnConfirmAndSave" class="btn btn-success px-4 fw-bold">
                    <i class="bi bi-check-circle me-2"></i>Lưu đơn và In ngay
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 767.98px) {
        .btn-check + .btn { font-size: 0.85rem !important; padding: 10px 4px !important; }
        .card { padding: 12px !important; }
        .form-select, .form-control { font-size: 0.9rem !important; }
        .modal-body { padding: 15px !important; }
    }
</style>
@endpush

@push('scripts')
    @vite(['resources/js/warehouse/export.js'])
@endpush
