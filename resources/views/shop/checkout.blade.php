@extends('layouts.shop')

@section('title', 'Thanh toán')

@section('content')
<h2 class="fw-bold mb-4">Thanh toán</h2>

<div class="row g-4">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('shop.checkout.store') }}" class="card border-0 shadow-sm">
            @csrf
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Họ tên</label>
                    <input name="buyer_name" class="form-control" value="{{ old('buyer_name', $customerUser?->name) }}" required>
                    @error('buyer_name')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số điện thoại</label>
                    <input name="phone" class="form-control" value="{{ old('phone', $customerUser?->phone) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Địa chỉ giao hàng</label>
                    <input name="address" class="form-control" value="{{ old('address', $customerUser?->customer?->address) }}" required>
                    @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ghi chú</label>
                    <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <button class="btn btn-primary fw-semibold">Đặt hàng</button>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-bold">Đơn hàng</h5>
                <div class="text-muted small mb-3">{{ $priceLabel }}</div>
                @foreach($items as $item)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>{{ $item['catalog']->product_name }} x {{ $item['quantity'] }}</div>
                        <strong>{{ number_format($item['total_amount']) }} đ</strong>
                    </div>
                @endforeach
                <div class="d-flex justify-content-between pt-3 fs-5">
                    <strong>Tổng</strong>
                    <strong class="text-primary">{{ number_format($totalAmount) }} đ</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
