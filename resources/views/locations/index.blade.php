@extends('layouts.admin')

@section('content')
<style>
    .master-page-header {
        background: #ffffff;
        border: 1px solid rgba(15, 23, 42, 0.06);
        border-radius: 1rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        padding: 1rem;
    }
    .page-kicker { color: #0d6efd; font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .icon-box {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #e7f1ff;
        color: #0d6efd;
        flex: 0 0 auto;
    }
</style>

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="master-page-header d-flex flex-column flex-md-row justify-content-between gap-3 mb-4 mt-2">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box d-none d-sm-inline-flex">
                <i class="bi bi-geo-alt fs-5"></i>
            </div>
            <div>
                <div class="page-kicker">Quản lý kho</div>
                <h4 class="fw-bold text-dark m-0">Vị trí kệ</h4>
                <div class="text-muted small">Quản lý vị trí lưu trữ hàng hóa trong kho</div>
            </div>
        </div>
        <a href="{{ route('locations.create') }}" class="btn btn-primary fw-bold rounded-3 align-self-start align-self-md-center">
            <i class="bi bi-plus-circle me-1"></i>Thêm vị trí
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3">
            <i class="bi bi-exclamation-triangle me-1"></i>{{ session('error') }}
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: .95rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Tên kệ / vị trí</th>
                        <th class="text-end pe-3" style="width: 170px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $l)
                        <tr>
                            <td class="ps-3">
                                <strong class="text-primary"><i class="bi bi-geo-alt me-1"></i>{{ $l->shelf_name }}</strong>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="{{ route('locations.edit', $l->id) }}" class="btn btn-sm btn-outline-primary fw-bold">
                                        <i class="bi bi-pencil-square me-1"></i>Sửa
                                    </a>
                                    <form action="{{ route('locations.destroy', $l->id) }}" method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa vị trí này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold">
                                            <i class="bi bi-trash me-1"></i>Xóa
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center py-5 text-muted">
                                <i class="bi bi-inboxes d-block fs-2 mb-2"></i>
                                Chưa có vị trí kệ nào.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
