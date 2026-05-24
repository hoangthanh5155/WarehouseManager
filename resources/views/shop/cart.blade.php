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
        <div class="shop-cart-table-wrap">
            <table class="table align-middle mb-0 shop-cart-table">
                <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="text-end">Giá</th>
                        <th style="width:136px;">SL</th>
                        <th class="text-end">Tổng</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="shop-cart-row">
                            <td class="fw-semibold shop-cart-product" data-label="Sản phẩm">{{ $item['catalog']->product_name }}</td>
                            <td class="text-end shop-cart-price" data-label="Giá">{{ number_format($item['unit_price']) }} đ</td>
                            <td class="shop-cart-qty" data-label="SL">
                                <div class="shop-qty-stepper" data-shop-qty-stepper>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-shop-qty-minus aria-label="Giảm số lượng">−</button>
                                    <input class="form-control form-control-sm text-center" type="number" min="0" max="99" name="items[{{ $item['catalog']->id }}]" value="{{ $item['quantity'] }}" inputmode="numeric">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-shop-qty-plus aria-label="Tăng số lượng">+</button>
                                </div>
                            </td>
                            <td class="text-end fw-bold shop-cart-total" data-label="Tổng">{{ number_format($item['total_amount']) }} đ</td>
                            <td class="text-end shop-cart-remove">
                                <button type="submit" form="remove-{{ $item['catalog']->id }}" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
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
                <a href="{{ route('shop.checkout') }}" class="btn btn-primary">Đặt hàng</a>
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
