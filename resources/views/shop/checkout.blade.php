@extends('layouts.shop')

@section('title', 'Đặt hàng')

@section('content')
<div class="shop-form-shell">
    <div class="mb-3">
        <h2 class="fw-bold mb-1">Đặt hàng</h2>
    </div>

    <div class="row g-3 align-items-start">
        <div class="col-lg-7">
            <form method="POST" action="{{ route('shop.checkout.store') }}" class="card border-0 shadow-sm shop-card shop-form-card">
                @csrf
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Họ tên</label>
                        <input name="buyer_name" class="form-control form-control-sm" value="{{ old('buyer_name', $customerUser?->name) }}" required>
                        @error('buyer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Số điện thoại</label>
                        <input name="phone" class="form-control form-control-sm" value="{{ old('phone', $customerUser?->phone) }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Địa chỉ giao hàng</label>
                        <input name="address" class="form-control form-control-sm" value="{{ old('address', $customerUser?->customer?->address) }}" required>
                        @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Ghi chú</label>
                        <textarea name="note" class="form-control form-control-sm" rows="2">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div class="card-footer bg-white text-end py-2">
                    <button class="btn btn-primary btn-sm fw-semibold px-3">Đặt hàng</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm shop-card shop-order-summary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="fw-bold mb-0">Đơn hàng</h5>
                            <div class="text-muted small">{{ $priceLabel }}</div>
                        </div>
                        <span class="badge text-bg-light border">{{ $items->sum('quantity') }} sản phẩm</span>
                    </div>
                    <div class="vstack gap-2">
                        @foreach($items as $item)
                            <div class="shop-summary-item">
                                <div class="small fw-semibold text-dark">{{ $item['catalog']->product_name }}</div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>x {{ $item['quantity'] }}</span>
                                    <strong class="text-dark">{{ number_format($item['total_amount']) }} đ</strong>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                        <strong>Tổng</strong>
                        <strong class="text-primary fs-5">{{ number_format($totalAmount) }} đ</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
