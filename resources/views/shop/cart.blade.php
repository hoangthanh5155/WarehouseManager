@extends('layouts.shop')

@section('title', 'Giỏ hàng')

@section('content')
<h2 class="fw-bold mb-3">Giỏ hàng</h2>

@if($items->isEmpty())
    <div class="alert alert-light border">Giỏ hàng đang trống.</div>
    <a href="{{ route('shop.index') }}" class="btn btn-primary">Tiếp tục mua hàng</a>
@else
    <form method="POST" action="{{ route('shop.cart.update') }}" class="card border-0 shadow-sm shop-card">
        @csrf
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Sản phẩm</th><th class="text-end">Giá</th><th style="width:112px;">SL</th><th class="text-end">Tổng</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="fw-semibold">{{ $item['catalog']->product_name }}</td>
                            <td class="text-end">{{ number_format($item['unit_price']) }} đ</td>
                            <td><input class="form-control form-control-sm" type="number" min="0" name="items[{{ $item['catalog']->id }}]" value="{{ $item['quantity'] }}"></td>
                            <td class="text-end fw-bold">{{ number_format($item['total_amount']) }} đ</td>
                            <td class="text-end">
                                <button type="submit" form="remove-{{ $item['catalog']->id }}" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex flex-column flex-md-row justify-content-between gap-3">
            <div class="fw-bold fs-5">Tổng: {{ number_format($totalAmount) }} đ</div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary">Cập nhật</button>
                <a href="{{ route('shop.checkout') }}" class="btn btn-primary">Thanh toán</a>
            </div>
        </div>
    </form>

    @foreach($items as $item)
        <form id="remove-{{ $item['catalog']->id }}" method="POST" action="{{ route('shop.cart.remove') }}">
            @csrf
            <input type="hidden" name="product_catalog_id" value="{{ $item['catalog']->id }}">
        </form>
    @endforeach
@endif
@endsection
