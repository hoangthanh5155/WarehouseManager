@extends('layouts.admin')

@section('title', 'Tài khoản khách hàng')

@section('content')
@php
    $accountLabel = ['retail' => 'Khách lẻ', 'store' => 'Cửa hàng'];
    $customerLabel = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'];
    $approvalLabel = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold mb-0">Tài khoản khách hàng</h3>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Tên</th><th>Email</th><th>Điện thoại</th><th>Tài khoản</th><th>Nhóm khách</th><th>Duyệt</th><th>Hoạt động</th><th>Khách hàng</th><th>Ngày tạo</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account->name }}</td>
                        <td>{{ $account->email }}</td>
                        <td>{{ $account->phone }}</td>
                        <td>{{ $accountLabel[$account->account_type] ?? $account->account_type }}</td>
                        <td>{{ $customerLabel[$account->customer_type] ?? $account->customer_type }}</td>
                        <td>{{ $approvalLabel[$account->approval_status] ?? $account->approval_status }}</td>
                        <td>{{ $account->is_active ? 'Có' : 'Không' }}</td>
                        <td>{{ $account->customer?->name ?: '-' }}</td>
                        <td>{{ optional($account->created_at)->format('d/m/Y') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('sales.customer_accounts.edit', $account) }}">Sửa</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Chưa có dữ liệu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($accounts->hasPages())<div class="card-footer bg-white">{{ $accounts->links() }}</div>@endif
</div>
@endsection
