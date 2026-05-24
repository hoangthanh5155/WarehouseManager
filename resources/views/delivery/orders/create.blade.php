@extends('layouts.admin')

@section('title', 'Tạo đơn cần giao')

@section('content')
<div class="container-fluid px-1 px-md-2" id="deliveryOrderCreatePage">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <h3 class="fw-bold text-dark mb-0">Tạo đơn cần giao</h3>
        <a href="{{ route('delivery.orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Danh sách đơn
        </a>
    </div>

    <form class="card border-0 shadow-sm delivery-api-form" method="POST" data-method="POST" data-endpoint="{{ route('delivery.orders.store') }}" data-success-redirect="{{ route('delivery.orders.index') }}">
        @csrf
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Người mua <span class="text-danger">*</span></label>
                    <input type="text" name="buyer_name" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Nhóm khách</label>
                    <select name="customer_type" class="form-select">
                        <option value="retail">Khách lẻ</option>
                        <option value="agency">Đại lý</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Khách hàng</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Khách mới</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Công ty</label>
                    <input type="text" name="company_name" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Địa chỉ</label>
                    <input type="text" name="address" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Mã số thuế</label>
                    <input type="text" name="tax_code" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">Ghi chú</label>
                    <textarea name="note" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Dòng hàng</h5>
                <button type="button" class="btn btn-outline-primary btn-sm" data-add-delivery-item>
                    <i class="bi bi-plus-lg me-1"></i>Thêm dòng
                </button>
            </div>

            <div class="vstack gap-2" id="deliveryOrderItems">
                <div class="row g-2 align-items-end delivery-order-item">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Sản phẩm</label>
                        <select class="form-select" name="items[0][product_catalog_id]" required>
                            <option value="">Chọn sản phẩm</option>
                            @foreach($productCatalogs as $catalog)
                                <option value="{{ $catalog->id }}" data-price="{{ (float) $catalog->retail_price }}">
                                    {{ $catalog->product_name }} (tồn {{ $catalog->stock_count }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Số lượng</label>
                        <input type="number" min="1" name="items[0][quantity]" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Đơn giá</label>
                        <input type="number" min="0" step="1000" name="items[0][unit_price]" class="form-control" value="0" required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-danger w-100" data-remove-delivery-item title="Xóa dòng">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('delivery.orders.index') }}" class="btn btn-light border">Hủy</a>
            <button type="submit" class="btn btn-primary fw-semibold">
                <i class="bi bi-check2 me-1"></i>Tạo đơn
            </button>
        </div>
    </form>
</div>

<template id="deliveryOrderItemTemplate">
    <div class="row g-2 align-items-end delivery-order-item">
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Sản phẩm</label>
            <select class="form-select" data-name="items[__INDEX__][product_catalog_id]" required>
                <option value="">Chọn sản phẩm</option>
                @foreach($productCatalogs as $catalog)
                    <option value="{{ $catalog->id }}" data-price="{{ (float) $catalog->retail_price }}">
                        {{ $catalog->product_name }} (tồn {{ $catalog->stock_count }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-semibold">Số lượng</label>
            <input type="number" min="1" data-name="items[__INDEX__][quantity]" class="form-control" value="1" required>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold">Đơn giá</label>
            <input type="number" min="0" step="1000" data-name="items[__INDEX__][unit_price]" class="form-control" value="0" required>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger w-100" data-remove-delivery-item title="Xóa dòng">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>
@endsection

@push('scripts')
    @vite(['resources/js/delivery-batches.js'])
@endpush
