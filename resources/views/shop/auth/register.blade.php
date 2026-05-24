@extends('layouts.shop')

@section('title', 'Đăng ký khách hàng')

@section('content')
<div class="card border-0 shadow-sm mx-auto" style="max-width: 520px;">
    <div class="card-body p-4">
        <h3 class="fw-bold mb-3">Đăng ký khách hàng</h3>
        <div class="alert alert-info">Tài khoản mới mặc định là khách lẻ và chỉ thấy giá bán lẻ.</div>
        <form method="POST" action="{{ route('shop.register.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Họ tên</label>
                <input name="name" class="form-control" required>
                @error('name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" required>
                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Điện thoại</label>
                <input name="phone" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" required>
                @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nhập lại mật khẩu</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Đăng ký</button>
        </form>
    </div>
</div>
@endsection
