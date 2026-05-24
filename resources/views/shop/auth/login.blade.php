@extends('layouts.shop')

@section('title', 'Đăng nhập khách hàng')

@section('content')
<div class="card border-0 shadow-sm mx-auto" style="max-width: 480px;">
    <div class="card-body p-4">
        <h3 class="fw-bold mb-3">Đăng nhập</h3>
        <form method="POST" action="{{ route('shop.login.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required>
                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Đăng nhập</button>
        </form>
        <div class="mt-3 text-center"><a href="{{ route('shop.register') }}">Tạo tài khoản mới</a></div>
    </div>
</div>
@endsection
