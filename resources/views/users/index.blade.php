@extends('layouts.admin')

@section('title', 'Quản lý người dùng')

@section('content')
<div class="container-fluid px-1 px-md-2 mb-5">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Quản lý người dùng</h3>
            <div class="text-muted">Tài khoản nội bộ cho Chủ kho, quản lý và nhân viên.</div>
        </div>
        <a href="{{ route('users.create') }}" class="btn btn-primary fw-bold align-self-md-start">
            <i class="bi bi-person-plus me-1"></i> Tạo người dùng
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="card border-0 shadow-sm rounded-4 d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Người dùng</th>
                        <th>Vai trò</th>
                        <th>Trạng thái</th>
                        <th>Đăng nhập gần nhất</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary-subtle text-primary rounded-3 d-grid place-items-center" style="width:38px;height:38px;display:grid;">
                                        <i class="bi {{ $user->roleIcon() }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $user->displayName() }}</div>
                                        <small class="text-muted">{{ $user->email }}{{ $user->phone ? ' · ' . $user->phone : '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $user->roleLabel() }}</span></td>
                            <td>
                                <span class="badge {{ $user->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                    {{ $statusLabels[$user->status] ?? $user->status }}
                                </span>
                            </td>
                            <td class="text-muted">{{ optional($user->last_login_at)->format('d/m/Y H:i') ?: 'Chưa đăng nhập' }}</td>
                            <td class="text-end text-nowrap">
                                @if(auth()->user()->canManageUser($user))
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary fw-bold">Sửa</a>
                                    @if(auth()->id() !== $user->id)
                                        <form action="{{ route('users.toggleStatus', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-secondary fw-bold" type="submit">
                                                {{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-muted small">Không có quyền</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Chưa có người dùng.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-md-none d-flex flex-column gap-2">
        @forelse($users as $user)
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3">
                    <div class="d-flex gap-2 align-items-start">
                        <div class="bg-primary-subtle text-primary rounded-3" style="width:38px;height:38px;display:grid;place-items:center;">
                            <i class="bi {{ $user->roleIcon() }}"></i>
                        </div>
                        <div class="min-w-0 flex-grow-1">
                            <div class="fw-bold text-dark">{{ $user->displayName() }}</div>
                            <div class="text-muted small">{{ $user->email }}</div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="badge bg-light text-dark border">{{ $user->roleLabel() }}</span>
                                <span class="badge {{ $user->status === 'active' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' }}">
                                    {{ $statusLabels[$user->status] ?? $user->status }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @if(auth()->user()->canManageUser($user))
                        <div class="d-flex gap-2 mt-3">
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-primary fw-bold flex-fill">Sửa</a>
                            @if(auth()->id() !== $user->id)
                                <form action="{{ route('users.toggleStatus', $user) }}" method="POST" class="flex-fill">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-secondary fw-bold w-100" type="submit">
                                        {{ $user->status === 'active' ? 'Khóa' : 'Mở khóa' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="card border-0 shadow-sm rounded-4"><div class="card-body text-center text-muted">Chưa có người dùng.</div></div>
        @endforelse
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
</div>
@endsection
