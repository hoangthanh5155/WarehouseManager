@extends('layouts.admin')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
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

<div id="app-content" class="container-fluid px-2 px-md-4" style="max-width: 900px; margin: 0 auto;">
    
    <div class="d-flex align-items-center gap-3 mb-4 mt-2">
        <a href="{{ url('/') }}" class="btn btn-light bg-white shadow-sm border rounded-3 px-3 py-2 text-decoration-none flex-shrink-0">
            <span class="fw-bold text-dark text-nowrap">⬅️ Trở về</span>
        </a>
        <h4 class="m-0 fw-bold text-uppercase text-dark lh-base">📦 NHẬP KHO</h4>
    </div>

    <ul class="nav nav-pills custom-tabs flex-column flex-md-row nav-fill mb-4" id="warehouseTab">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab_fast_scan">🔍 1. NHẬP (CÓ SN)</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_auto_sn">➕ 2. NHẬP (CHƯA SN)</a></li>
    </ul>

    <div class="tab-content card p-3 p-md-4 shadow border-0 rounded-4 mb-5">
        
        <div class="tab-pane fade show active" id="tab_fast_scan">
            <div class="d-flex flex-column gap-3 mb-4 p-3 bg-light border rounded context-group">
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <span class="fw-bold small text-secondary">THÔNG TIN NHẬP HÀNG LÔ</span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 btn-clear-form" data-tab="1">🧹 Làm mới</button>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">1. Gắn Nhà Cung Cấp</label>
                    <input type="text" id="fast_sup" class="form-control form-control-lg smart-input input-supplier" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off">
                    <div class="smart-menu">
                        @foreach($suppliers as $s) 
                            <div class="smart-option">{{ $s->name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none">➕ Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">2. Gắn Tên Sản Phẩm</label>
                    <input type="text" id="fast_prod" class="form-control form-control-lg smart-input input-product" data-suggestion-url="{{ route('products.suggestion') }}" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off">
                    <div class="smart-menu menu-product">
                        @foreach($all_catalogs as $cat) 
                            <div class="smart-option" data-supplier="{{ $cat->supplier ? $cat->supplier->name : '' }}">{{ $cat->product_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none">➕ Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">3. Gắn Vị Trí Kệ</label>
                    <input type="text" id="fast_loc" class="form-control form-control-lg smart-input input-location" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off">
                    <div class="smart-menu menu-location">
                        @foreach($locations as $l) 
                            <div class="smart-option" data-shelf="{{ $l->shelf_name }}">{{ $l->shelf_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none">➕ Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="mb-1 position-relative">
                    <label class="fw-bold small text-secondary mb-1">GIÁ SỈ GỐC / GIÁ NHẬP (VNĐ)</label>
                    <input type="number" id="fast_wholesale_price" class="form-control form-control-lg price-input input-wholesale" placeholder="Nhập số tiền" autocomplete="off">
                    <div id="suggestions_fast_wholesale_price" class="d-flex flex-wrap gap-2 mt-2 suggestion-container"></div>
                </div>
            </div>

            <div>
                <label class="fw-bold text-primary mb-1">QUÉT MÃ SN VÀO ĐÂY</label>
                <input type="text" id="fast_sn_input" class="form-control form-control-lg border-primary shadow text-center" data-store-url="{{ route('products.store') }}" placeholder="Bắn súng quét... (Tự động lưu)" style="height: 60px; font-size: 1.3rem;" autocomplete="off">
            </div>
            <div class="mt-4"><ul id="scan_log" class="list-group" style="max-height: 250px; overflow-y: auto;"></ul></div>
        </div>

        <div class="tab-pane fade" id="tab_auto_sn">
            <form action="{{ route('products.store') }}" method="POST" id="form_auto_sn" class="d-flex flex-column gap-3 context-group">
                @csrf
                <div class="d-flex justify-content-between align-items-center mb-0">
                    <span class="fw-bold small text-secondary">TẠO MÃ HÀNG LOẠT</span>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 btn-clear-form" data-tab="2">🧹 Làm mới</button>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Nhà Cung Cấp</label>
                    <input type="text" id="auto_sup" name="supplier_id" class="form-control form-control-lg smart-input input-supplier" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off" required>
                    <div class="smart-menu">
                        @foreach($suppliers as $s) 
                            <div class="smart-option">{{ $s->name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none">➕ Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Tên Sản Phẩm</label>
                    <input type="text" id="auto_prod" name="product_catalog_id" class="form-control form-control-lg smart-input input-product" data-suggestion-url="{{ route('products.suggestion') }}" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off" required>
                    <div class="smart-menu menu-product">
                        @foreach($all_catalogs as $cat) 
                            <div class="smart-option" data-supplier="{{ $cat->supplier ? $cat->supplier->name : '' }}">{{ $cat->product_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none">➕ Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="smart-input-container">
                    <label class="fw-bold small text-secondary mb-1">Vị Trí Kệ</label>
                    <input type="text" id="auto_loc" name="location_id" class="form-control form-control-lg smart-input input-location" placeholder="Gõ chọn hoặc nhập mới..." autocomplete="off" required>
                    <div class="smart-menu menu-location">
                        @foreach($locations as $l) 
                            <div class="smart-option" data-shelf="{{ $l->shelf_name }}">{{ $l->shelf_name }}</div> 
                        @endforeach
                        <div class="smart-option smart-add-new d-none">➕ Thêm mới: <span class="new-text"></span></div>
                    </div>
                </div>

                <div class="mb-1 position-relative">
                    <label class="fw-bold small text-secondary mb-1">GIÁ SỈ GỐC / GIÁ NHẬP (VNĐ)</label>
                    <input type="number" name="wholesale_price" id="wholesale_price" class="form-control form-control-lg price-input input-wholesale" placeholder="Nhập số tiền" autocomplete="off" required>
                    <div id="suggestions_wholesale_price" class="d-flex flex-wrap gap-2 mt-2 suggestion-container"></div>
                </div>

                <div>
                    <label class="fw-bold small text-secondary mb-1">SỐ LƯỢNG MUỐN TẠO (Tối đa 100)</label>
                    <input type="number" name="quantity" id="auto_quantity" class="form-control form-control-lg shadow-sm" min="1" max="100" style="border: 2px solid #ffc107;" required>
                </div>
                <button type="submit" id="btn_submit_auto" class="btn btn-success btn-lg mt-3 py-3 fw-bold shadow">🚀 TẠO MÃ & IN TEM</button>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="printModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold m-0">🖨️ XEM TRƯỚC TEM IN</h5>
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
                <button type="button" id="btn_execute_print" class="btn btn-success btn-lg fw-bold px-5">🖨️ PHÁT LỆNH IN</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @vite(['resources/js/app.js'])
@endsection