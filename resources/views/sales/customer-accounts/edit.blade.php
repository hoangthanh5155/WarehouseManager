@extends('layouts.admin')

@section('title', 'Sửa tài khoản khách hàng')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">{{ $customerPortalUser->name }}</h3>
        <div class="text-muted">{{ $customerPortalUser->email }}</div>
    </div>
    <a href="{{ route('sales.customer_accounts.index') }}" class="btn btn-outline-secondary">Quay lại</a>
</div>

<form method="POST" action="{{ route('sales.customer_accounts.update', $customerPortalUser) }}" class="card border-0 shadow-sm" style="max-width: 760px;">
    @csrf
    @method('PUT')
    <div class="card-body row g-3">
        <div class="col-md-12">
            <label class="form-label fw-semibold">Khách hàng liên kết</label>
            <select name="customer_id" class="form-select">
                <option value="">Không liên kết</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected($customerPortalUser->customer_id === $customer->id)>{{ $customer->name }} - {{ $customer->phone }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Loại tài khoản</label>
            <select name="account_type" class="form-select">
                <option value="retail" @selected($customerPortalUser->account_type === 'retail')>Khách lẻ</option>
                <option value="store" @selected($customerPortalUser->account_type === 'store')>Cửa hàng</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Nhóm giá</label>
            <select name="customer_type" class="form-select">
                <option value="retail" @selected($customerPortalUser->customer_type === 'retail')>Khách lẻ</option>
                <option value="agency" @selected($customerPortalUser->customer_type === 'agency')>Đại lý</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Trạng thái duyệt</label>
            <select name="approval_status" class="form-select">
                <option value="pending" @selected($customerPortalUser->approval_status === 'pending')>Chờ duyệt</option>
                <option value="approved" @selected($customerPortalUser->approval_status === 'approved')>Đã duyệt</option>
                <option value="rejected" @selected($customerPortalUser->approval_status === 'rejected')>Từ chối</option>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive" @checked($customerPortalUser->is_active)>
                <label class="form-check-label fw-semibold" for="isActive">Đang hoạt động</label>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary">Lưu</button>
    </div>
</form>
@endsection
