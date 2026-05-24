@extends('layouts.admin')

@section('title', 'Lịch sử kho')

@section('content')
<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="bg-white border-0 shadow-sm rounded-4 p-3 p-md-4 mb-3">
        <div class="text-uppercase text-primary fw-bold small mb-1">Báo cáo</div>
        <h3 class="fw-bold text-dark m-0">Lịch sử kho</h3>
        <div class="text-muted small mt-1">{{ $startDate->format('d/m/Y') }} - {{ $endDate->format('d/m/Y') }}</div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4 p-md-5 text-center">
            <div class="bg-warning-subtle text-warning rounded-circle mx-auto mb-3 d-grid" style="width:56px;height:56px;place-items:center;">
                <i class="bi bi-database-exclamation fs-3"></i>
            </div>
            <h5 class="fw-bold text-dark mb-2">Chưa có dữ liệu.</h5>
        </div>
    </div>
</div>
@endsection
