@extends('layouts.admin')

@section('title', 'Sửa người dùng')

@section('content')
<div class="container-fluid px-1 px-md-2 mb-5" style="max-width: 980px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Sửa người dùng</h3>
            <div class="text-muted">{{ $managedUser->email }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3 p-md-4">
            @if(session('temporary_password'))
                <div class="alert alert-warning border-0 shadow-sm">
                    <div class="fw-bold">Mật khẩu tạm, chỉ hiển thị một lần:</div>
                    <code class="fs-6">{{ session('temporary_password') }}</code>
                </div>
            @endif
            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('users.update', $managedUser) }}">
                @method('PUT')
                @include('users._form')
            </form>
            @if(auth()->user()->canManageUser($managedUser))
                <hr>
                <form method="POST" action="{{ route('users.resetPassword', $managedUser) }}" onsubmit="return confirm('Đặt lại mật khẩu tạm cho tài khoản này?');">
                    @csrf
                    <button class="btn btn-outline-warning fw-bold" type="submit">
                        <i class="bi bi-key me-1"></i> Đặt lại mật khẩu
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
