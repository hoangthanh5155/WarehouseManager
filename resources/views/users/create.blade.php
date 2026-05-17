@extends('layouts.admin')

@section('title', 'Tạo người dùng')

@section('content')
<div class="container-fluid px-1 px-md-2 mb-5" style="max-width: 980px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tạo người dùng nội bộ</h3>
            <div class="text-muted">Tạo tài khoản cho nhân sự trong kho.</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('users.store') }}">
                @include('users._form')
            </form>
        </div>
    </div>
</div>
@endsection
