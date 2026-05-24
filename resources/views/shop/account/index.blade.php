@extends('layouts.shop')

@section('title', 'Tài khoản')

@section('content')
@php
    $accountLabel = ['retail' => 'Khách lẻ', 'store' => 'Cửa hàng'];
    $customerLabel = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
    $approvalLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
@endphp

<h2 class="fw-bold mb-4">Tài khoản</h2>
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6"><div class="text-muted">Tên</div><strong>{{ $customerUser->name }}</strong></div>
            <div class="col-md-6"><div class="text-muted">Email</div><strong>{{ $customerUser->email }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Tài khoản</div><strong>{{ $accountLabel[$customerUser->account_type] ?? $customerUser->account_type }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Nhóm khách</div><strong>{{ $customerLabel[$customerUser->customer_type] ?? $customerUser->customer_type }}</strong></div>
            <div class="col-md-4"><div class="text-muted">Duyệt</div><strong>{{ $approvalLabel[$customerUser->approval_status] ?? $customerUser->approval_status }}</strong></div>
        </div>
        <a href="{{ route('shop.account.orders') }}" class="btn btn-primary mt-4">Lịch sử đơn</a>
    </div>
</div>
@endsection
