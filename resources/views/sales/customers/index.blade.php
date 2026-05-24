@extends('layouts.admin')

@section('title', 'Khách hàng')

@section('content')
@php($customerLabel = ['retail' => 'Khách lẻ', 'agency' => 'Đại lý'])

<h3 class="fw-bold mb-4">Khách hàng</h3>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Tên</th><th>Công ty</th><th>Điện thoại</th><th>Loại</th><th>Đơn hàng</th><th>Ngày tạo</th></tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td class="fw-semibold">{{ $customer->name }}</td>
                        <td>{{ $customer->company_name ?: '-' }}</td>
                        <td>{{ $customer->phone ?: '-' }}</td>
                        <td>{{ $customerLabel[$customer->type] ?? $customer->type }}</td>
                        <td>{{ number_format($customer->fulfillment_orders_count) }}</td>
                        <td>{{ optional($customer->created_at)->format('d/m/Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Chưa có dữ liệu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="card-footer bg-white">{{ $customers->links() }}</div>@endif
</div>
@endsection
