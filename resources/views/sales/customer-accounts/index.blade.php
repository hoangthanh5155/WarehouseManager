@extends('layouts.admin')

@section('title', 'Tài khoản khách hàng')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">Tài khoản khách hàng</h3>
        <div class="text-muted">Chỉ admin/chủ kho được nâng quyền cửa hàng/đại lý.</div>
    </div>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Tên</th><th>Email</th><th>Phone</th><th>Account</th><th>Customer</th><th>Duyệt</th><th>Active</th><th>Liên kết</th><th>Ngày tạo</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    <tr>
                        <td class="fw-semibold">{{ $account->name }}</td>
                        <td>{{ $account->email }}</td>
                        <td>{{ $account->phone }}</td>
                        <td>{{ $account->account_type }}</td>
                        <td>{{ $account->customer_type }}</td>
                        <td>{{ $account->approval_status }}</td>
                        <td>{{ $account->is_active ? 'Có' : 'Không' }}</td>
                        <td>{{ $account->customer?->name ?: '-' }}</td>
                        <td>{{ optional($account->created_at)->format('d/m/Y') }}</td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('sales.customer_accounts.edit', $account) }}">Sửa</a></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-4">Chưa có tài khoản khách hàng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($accounts->hasPages())<div class="card-footer bg-white">{{ $accounts->links() }}</div>@endif
</div>
@endsection
