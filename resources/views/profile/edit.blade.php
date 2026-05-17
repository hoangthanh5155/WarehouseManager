@extends('layouts.admin')

@section('title', 'Hồ sơ cá nhân')

@section('content')
<div class="container-fluid px-1 px-md-2 mb-5" style="max-width: 980px;">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Hồ sơ cá nhân</h3>
            <div class="text-muted">{{ $user->displayName() }} · {{ $user->roleLabel() }}</div>
        </div>
    </div>

    @if(session('password_required'))
        <div class="alert alert-warning border-0 shadow-sm">{{ session('password_required') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="bg-primary-subtle text-primary rounded-3" style="width:40px;height:40px;display:grid;place-items:center;">
                            <i class="bi {{ $user->roleIcon() }}"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0">Thông tin cá nhân</h5>
                            <small class="text-muted">Vai trò không thể tự thay đổi.</small>
                        </div>
                    </div>

                    @if(session('profile_success'))
                        <div class="alert alert-success py-2">{{ session('profile_success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tên đăng nhập</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->name }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Tên hiển thị</label>
                            <input type="text" name="display_name" class="form-control" value="{{ old('display_name', $user->display_name) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">SĐT</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Vai trò</label>
                            <input type="text" class="form-control bg-light" value="{{ $user->roleLabel() }}" readonly>
                        </div>

                        @if($errors->hasAny(['display_name', 'email', 'phone']))
                            <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                        @endif

                        <button class="btn btn-primary fw-bold" type="submit">Lưu hồ sơ</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6" id="password">
            <div class="card border-0 shadow-sm rounded-4 h-100 {{ $forcePassword ? 'border border-warning' : '' }}">
                <div class="card-body p-3 p-md-4">
                    <h5 class="fw-bold mb-1">Đổi mật khẩu</h5>
                    <div class="text-muted small mb-3">Mật khẩu mới tối thiểu 8 ký tự.</div>

                    @if(session('password_success'))
                        <div class="alert alert-success py-2">{{ session('password_success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Mật khẩu hiện tại</label>
                            <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                            @error('current_password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Mật khẩu mới</label>
                            <input type="password" name="password" class="form-control" required autocomplete="new-password">
                            @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Nhập lại mật khẩu mới</label>
                            <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                        </div>

                        <button class="btn btn-warning fw-bold" type="submit">
                            <i class="bi bi-key me-1"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
