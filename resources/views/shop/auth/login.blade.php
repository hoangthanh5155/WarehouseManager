@extends('layouts.shop')

@section('title', 'Đăng nhập')

@section('content')
<div class="shop-auth-wrap">
    <div class="card border-0 shadow-sm shop-card shop-auth-card mx-auto">
        <div class="card-body">
            <h3 class="fw-bold mb-3">Đăng nhập</h3>

            <form method="POST" action="{{ route('shop.login.store') }}">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control form-control-sm" required>
                    @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mật khẩu</label>
                    <input type="password" name="password" class="form-control form-control-sm" required>
                </div>
                <button class="btn btn-primary btn-sm w-100 fw-semibold">Đăng nhập</button>
            </form>

            <div class="text-center mt-3 small">
                <a href="{{ route('shop.index') }}">Tiếp tục mua hàng</a>
            </div>
            <div class="text-center mt-2 small text-muted">
                Chưa có tài khoản? <a href="{{ route('shop.register') }}">Đăng ký</a>
            </div>
        </div>
    </div>
</div>
@endsection
