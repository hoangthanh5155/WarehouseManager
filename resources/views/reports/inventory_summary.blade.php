@extends('layouts.admin')

@section('title', 'Nhập xuất tồn')

@section('content')
<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <div class="text-uppercase text-primary fw-bold small mb-1">Báo cáo - Thống kê</div>
        <h3 class="fw-bold text-dark m-0">Nhập xuất tồn</h3>
        <div class="text-muted small mt-1">Báo cáo tổng hợp tồn kho theo sản phẩm sẽ được tách thành bước tiếp theo.</div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 text-center text-muted">
            <i class="bi bi-bar-chart-line fs-1 d-block mb-2 text-primary"></i>
            <div class="fw-bold text-dark mb-1">Đang phát triển</div>
            <div>Bạn có thể dùng “Lịch sử kho” để xem từng lần nhập/xuất theo serial trong giai đoạn này.</div>
            <a href="{{ route('reports.warehouse_history') }}" class="btn btn-primary fw-bold mt-3">
                <i class="bi bi-clock-history me-1"></i>Xem lịch sử kho
            </a>
        </div>
    </div>
</div>
@endsection
