@extends('layouts.admin')

@section('title', 'Bàn làm việc')

@section('content')
<div class="container-fluid px-2 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-2 mb-4 mt-2">
        <div>
            <h3 class="fw-bold text-dark mb-1">Bàn làm việc</h3>
            <div class="text-muted">Truy cập nhanh theo nhóm nghiệp vụ</div>
        </div>

        @if($selectedGroup)
            <a href="{{ route('workbench.index') }}" class="btn btn-outline-secondary fw-semibold">
                <i class="bi bi-arrow-left me-1"></i>Quay lại Bàn làm việc
            </a>
        @endif
    </div>

    @if(empty($groups))
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="display-6 text-muted mb-3"><i class="bi bi-lock"></i></div>
                <h5 class="fw-bold">Chưa có chức năng khả dụng</h5>
                <p class="text-muted mb-0">Tài khoản của bạn chưa được cấp quyền thao tác trong hệ thống.</p>
            </div>
        </div>
    @elseif($selectedGroup)
        <section class="card border-0 shadow-sm workbench-detail">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="workbench-group-icon">
                        <i class="bi {{ $selectedGroup['icon'] }}"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1">{{ $selectedGroup['title'] }}</h4>
                        <div class="text-muted">{{ $selectedGroup['description'] }}</div>
                    </div>
                </div>

                <div class="row g-3">
                    @foreach($selectedGroup['actions'] as $action)
                        <div class="col-12 col-md-6 col-xl-4">
                            <a href="{{ $action['route'] }}" class="workbench-action text-decoration-none">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="workbench-action-icon">
                                        <i class="bi {{ $action['icon'] }}"></i>
                                    </div>
                                    <div class="min-w-0 flex-grow-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <strong class="text-dark">{{ $action['label'] }}</strong>
                                            @if($action['badge'])
                                                <span class="badge text-bg-primary">{{ $action['badge'] }}</span>
                                            @endif
                                        </div>
                                        <div class="text-muted small">{{ $action['description'] }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <div class="row g-3">
            @foreach($groups as $group)
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ route('workbench.index', ['group' => $group['key']]) }}" class="workbench-group-card text-decoration-none">
                        <div class="d-flex align-items-start gap-3">
                            <div class="workbench-group-icon">
                                <i class="bi {{ $group['icon'] }}"></i>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <h5 class="fw-bold text-dark mb-1">{{ $group['title'] }}</h5>
                                <div class="text-muted">{{ $group['description'] }}</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted mt-1"></i>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('styles')
<style>
    .workbench-detail,
    .workbench-group-card {
        border-radius: 10px;
    }

    .workbench-group-card {
        display: block;
        min-height: 136px;
        padding: 20px;
        border: 1px solid #e9ecef;
        background: #fff;
        box-shadow: 0 .125rem .5rem rgba(0, 0, 0, .04);
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .workbench-group-card:hover,
    .workbench-group-card:focus,
    .workbench-action:hover,
    .workbench-action:focus {
        border-color: #0d6efd;
        box-shadow: 0 .5rem 1rem rgba(13, 110, 253, .08);
        transform: translateY(-1px);
    }

    .workbench-group-icon,
    .workbench-action-icon {
        width: 46px;
        height: 46px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef4ff;
        color: #0d6efd;
        flex: 0 0 auto;
        font-size: 1.15rem;
    }

    .workbench-action {
        display: block;
        min-height: 112px;
        padding: 16px;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }

    .min-w-0 {
        min-width: 0;
    }

    @media (max-width: 767.98px) {
        .workbench-group-card,
        .workbench-action {
            min-height: 112px;
            padding: 18px;
        }
    }
</style>
@endpush
