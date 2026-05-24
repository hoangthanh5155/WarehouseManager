@extends('layouts.shop')

@section('title', 'Tài khoản')

@section('content')
<h2 class="fw-bold mb-4">Tài khoản</h2>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><div class="text-muted">Tên</div><strong>{{ $customerUser->name }}</strong></div>
            <div class="col-md-6"><div class="text-muted">Email</div><strong>{{ $customerUser->email }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Loại tài khoản</div><strong>{{ $customerUser->account_type }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Nhóm giá</div><strong>{{ $customerUser->customer_type }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Duyệt</div><strong>{{ $customerUser->approval_status }}</strong></div>
        </div>
        <a href="{{ route('shop.account.orders') }}" class="btn btn-primary mt-4">Lịch sử đơn</a>
    </div>
</div>
@endsection
