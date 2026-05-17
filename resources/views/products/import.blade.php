@extends('layouts.admin')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
    .import-page-shell { max-width: 980px; margin: 0 auto; }
    .page-header-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 1rem;
    }
    .page-kicker { color: #0d6efd; font-size: 0.78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .icon-box {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e7f1ff;
        color: #0d6efd;
        flex: 0 0 auto;
    }
    .section-card {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1rem;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
    }
    .soft-panel { background: #f8fafc; border: 1px solid #e9eef5; border-radius: 1rem; }
    .scan-panel {
        border: 1px solid #cfe2ff;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border-radius: 1rem;
        padding: 1rem;
    }
    .custom-tabs { background: #e9ecef; border-radius: 12px; padding: 5px; }
    .custom-tabs .nav-item { margin: 2px 0; }
    @media (min-width: 768px) { .custom-tabs .nav-item { margin: 0 2px; } }
    .custom-tabs .nav-link { color: #6c757d; font-weight: 600; border-radius: 8px; padding: 12px 5px; border: none; }
    .custom-tabs .nav-link.active { background: #0d6efd !important; color: #ffffff !important; box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3); }

    .smart-input-container { position: relative; }
    .smart-input { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%236c757d' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 15px center; background-size: 14px; padding-right: 40px; }
    .smart-menu { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ced4da; border-radius: 8px; margin-top: 5px; z-index: 1050; box-shadow: 0 6px 15px rgba(0,0,0,0.15); max-height: 160px; overflow-y: auto; }
    .smart-menu.show { display: block !important; }
    .smart-option { padding: 12px 15px; border-bottom: 1px solid #f8f9fa; cursor: pointer; color: #333; }
    .smart-option:hover { background: #e9ecef; }
    .smart-add-new { color: #0d6efd; font-weight: bold; background: #f1f5fa; position: sticky; bottom: 0; border-top: 1px solid #dee2e6; }
    
    .highlight-scan { animation: flashHighlight 1s ease-out; }
    @keyframes flashHighlight { 0% { background-color: #d1e7dd; transform: scale(1.02); } 100% { background-color: transparent; transform: scale(1); } }

    .btn-suggestion { background-color: #f1f5fa; color: #0d6efd; border: 1px solid #cfe2ff; font-weight: 600; font-size: 0.9rem; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: all 0.15s ease; }
    .btn-suggestion:hover { background-color: #0d6efd; color: #ffffff; }
</style>

@php
    $all_catalogs = \App\Models\ProductCatalog::with('supplier')->get();
@endphp

<div id="app-content" class="container-fluid px-2 px-md-4 import-page-shell">
    
    <div class="page-header-card d-flex align-items-center gap-3 mb-4 mt-2">
        <div class="icon-box d-none d-sm-inline-flex">
            <i class="bi bi-box-arrow-in-down fs-5"></i>
        </div>
        <div>
            <div class="page-kicker">Quản lý kho</div>
            <h4 class="m-0 fw-bold text-dark lh-base">Nhập kho</h4>
            <div class="text-muted small">Quét serial hoặc tạo mã hàng loạt cho sản phẩm nhập kho</div>
        </div>
    </div>

    <ul class="nav nav-pills custom-tabs flex-column flex-md-row nav-fill mb-4" id="warehouseTab">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab_fast_scan"><i class="bi bi-upc-scan me-1"></i>Nhập có serial</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_auto_sn"><i class="bi bi-magic me-1"></i>Tạo serial tự động</a></li>
    </ul>

    <div class="tab-content section-card p-3 p-md-4 mb-5">
        
        <div class="tab-pane fade show active" id="tab_fast_scan">
            <div class="d-flex flex-column gap-3 mb-4 p-3 soft-panel context-group">
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <span class="fw-bold small text-secondary"><i class="bi bi-clipboard-data me-1"></i>Thông tin lô nhập</span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 btn-clear-form" data-tab="1"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Nhà cung cấp</label>
                    <input type="text" id="fast_sup" class="form-control form-control-lg smart-input input-supplier" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off">
                    <div class="smart-menu">
                        @foreach($suppliers as $s) 
                            <div class="smart-option">{{ $s->name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none"><i class="bi bi-plus-circle me-1"></i>Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Tên sản phẩm</label>
                    <input type="text" id="fast_prod" class="form-control form-control-lg smart-input input-product" data-suggestion-url="{{ route('products.suggestion') }}" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off">
                    <div class="smart-menu menu-product">
                        @foreach($all_catalogs as $cat) 
                            <div class="smart-option" data-supplier="{{ $cat->supplier ? $cat->supplier->name : '' }}">{{ $cat->product_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none"><i class="bi bi-plus-circle me-1"></i>Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Vị trí kệ</label>
                    <input type="text" id="fast_loc" class="form-control form-control-lg smart-input input-location" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off">
                    <div class="smart-menu menu-location">
                        @foreach($locations as $l) 
                            <div class="smart-option" data-shelf="{{ $l->shelf_name }}">{{ $l->shelf_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none"><i class="bi bi-plus-circle me-1"></i>Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="mb-1 position-relative">
                    <label class="fw-bold small text-secondary mb-1">Giá sỉ gốc / giá nhập (VNĐ)</label>
                    <input type="number" id="fast_wholesale_price" class="form-control form-control-lg price-input input-wholesale" placeholder="Nhập số tiền" autocomplete="off">
                    <div id="suggestions_fast_wholesale_price" class="d-flex flex-wrap gap-2 mt-2 suggestion-container"></div>
                </div>
            </div>

            <div class="scan-panel">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="icon-box">
                        <i class="bi bi-upc-scan fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Quét serial</div>
                        <div class="text-muted small">Bắn mã SN để tự động lưu vào kho</div>
                    </div>
                </div>
                <input type="text" id="fast_sn_input" class="form-control form-control-lg border-primary shadow text-center" data-store-url="{{ route('products.store') }}" placeholder="Bắn súng quét... (Tự động lưu)" style="height: 60px; font-size: 1.3rem;" autocomplete="off">
            </div>
            <div class="mt-4"><ul id="scan_log" class="list-group" style="max-height: 250px; overflow-y: auto;"></ul></div>
        </div>

        <div class="tab-pane fade" id="tab_auto_sn">
            <form action="{{ route('products.store') }}" method="POST" id="form_auto_sn" class="d-flex flex-column gap-3 context-group">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <span class="fw-bold small text-secondary"><i class="bi bi-box-arrow-in-down me-1"></i>Tạo mã hàng loạt</span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 btn-clear-form" data-tab="2"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</button>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Nhà cung cấp</label>
                    <input type="text" id="auto_sup" name="supplier_id" class="form-control form-control-lg smart-input input-supplier" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off" required>
                    <div class="smart-menu">
                        @foreach($suppliers as $s) 
                            <div class="smart-option">{{ $s->name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none"><i class="bi bi-plus-circle me-1"></i>Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Tên sản phẩm</label>
                    <input type="text" id="auto_prod" name="product_catalog_id" class="form-control form-control-lg smart-input input-product" data-suggestion-url="{{ route('products.suggestion') }}" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off" required>
                    <div class="smart-menu menu-product">
                        @foreach($all_catalogs as $cat) 
                            <div class="smart-option" data-supplier="{{ $cat->supplier ? $cat->supplier->name : '' }}">{{ $cat->product_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none"><i class="bi bi-plus-circle me-1"></i>Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Vị trí kệ</label>
                    <input type="text" id="auto_loc" name="location_id" class="form-control form-control-lg smart-input input-location" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off" required>
                    <div class="smart-menu menu-location">
                        @foreach($locations as $l) 
                            <div class="smart-option" data-shelf="{{ $l->shelf_name }}">{{ $l->shelf_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none"><i class="bi bi-plus-circle me-1"></i>Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="mb-1 position-relative">
                    <label class="fw-bold small text-secondary mb-1">Giá sỉ gốc / giá nhập (VNĐ)</label>
                    <input type="number" name="wholesale_price" id="wholesale_price" class="form-control form-control-lg price-input input-wholesale" placeholder="Nhập số tiền" autocomplete="off" required>
                    <div id="suggestions_wholesale_price" class="d-flex flex-wrap gap-2 mt-2 suggestion-container"></div>
                </div>

                <div>
                    <label class="fw-bold small text-secondary mb-1">Số lượng muốn tạo (tối đa 100)</label>
                    <input type="number" name="quantity" id="auto_quantity" class="form-control form-control-lg shadow-sm" min="1" max="100" style="border: 2px solid #ffc107;" required>
                </div>
                <button type="submit" id="btn_submit_auto" class="btn btn-success btn-lg mt-3 py-3 fw-bold shadow">
                    <i class="bi bi-qr-code-scan me-2"></i>Tạo mã và xem tem
                </button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="printModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold m-0"><i class="bi bi-printer me-2"></i>Xem trước tem in</h5>
                <select id="label_size_selector" class="form-select form-select-sm text-dark ms-auto" style="width: 120px; cursor: pointer;">
                    <option value="small">35x22 mm</option>
                    <option value="medium" selected>50x30 mm</option>
                    <option value="large">60x40 mm</option>
                </select>
                <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-light">
                <div id="print_area" class="p-3 text-center d-flex flex-wrap justify-content-center"></div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tắt</button>
                <button type="button" id="btn_execute_print" class="btn btn-success btn-lg fw-bold px-5">
                    <i class="bi bi-printer-fill me-2"></i>In tem
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/app.js'])
@endsection
