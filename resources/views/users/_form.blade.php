@csrf
@php
    $isEditing = isset($managedUser);
    $isRoleReadonly = $roleReadonly ?? false;
    $selectedFeaturePermissions = old('feature_permissions', $assignedFeaturePermissions ?? []);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Tên đăng nhập</label>
        @if($isEditing)
            <input type="text" value="{{ $managedUser->name }}" class="form-control bg-light" readonly disabled>
            <div class="form-text">Tên đăng nhập dùng để đăng nhập và không thể sửa tại đây.</div>
        @else
            <input type="text" name="name" id="userNameInput" value="{{ old('name') }}" class="form-control" required>
            <div class="form-text">Chỉ dùng chữ, số, dấu gạch ngang, gạch dưới hoặc dấu chấm.</div>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Tên hiển thị</label>
        <input type="text" name="display_name" value="{{ old('display_name', $managedUser->display_name ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Email</label>
        <input type="email" name="email" value="{{ old('email', $managedUser->email ?? '') }}" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">SĐT</label>
        <input type="text" name="phone" value="{{ old('phone', $managedUser->phone ?? '') }}" class="form-control">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Vai trò</label>
        @if($isRoleReadonly)
            <input type="text" class="form-control bg-light" value="{{ $managedUser->roleLabel() }}" readonly disabled>
            <div class="form-text">Vai trò Chủ kho/root được bảo vệ và không thể sửa tại đây.</div>
        @else
            <select name="role" class="form-select" required>
                @foreach($manageableRoles as $role => $label)
                    <option value="{{ $role }}" @selected(old('role', $managedUser->role ?? '') === $role)>{{ $label }}</option>
                @endforeach
            </select>
        @endif
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Trạng thái</label>
        <select name="status" class="form-select" required>
            @foreach($statusLabels as $status => $label)
                <option value="{{ $status }}" @selected(old('status', $managedUser->status ?? 'active') === $status)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @unless($isEditing)
        <div class="col-12">
            <div class="border rounded-4 p-3 bg-light">
                <div class="d-flex align-items-start gap-2 mb-2">
                    <div class="bg-primary-subtle text-primary rounded-3 d-grid flex-shrink-0" style="width:38px;height:38px;place-items:center;">
                        <i class="bi bi-key"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">Mật khẩu tạm ban đầu</div>
                        <div class="text-muted small">Đây là mật khẩu tạm. Người dùng sẽ phải đổi mật khẩu ở lần đăng nhập đầu tiên.</div>
                    </div>
                </div>
                <div class="row g-2 align-items-center">
                    <div class="col-md">
                        <input type="text" id="initialPasswordPreview" name="initial_password" class="form-control bg-white fw-bold" readonly>
                    </div>
                    <div class="col-md-auto">
                        <button type="button" id="regenerateInitialPassword" class="btn btn-outline-primary fw-bold w-100">
                            <i class="bi bi-arrow-clockwise me-1"></i>Tạo lại mật khẩu
                        </button>
                    </div>
                </div>
                <div class="form-text">Chỉ chia sẻ mật khẩu này cho đúng người dùng.</div>
            </div>
        </div>
    @endunless

    @if($isEditing && ($canManageFeaturePermissions ?? false))
        <div class="col-12">
            <div class="border rounded-4 p-3 bg-light">
                <div class="fw-bold text-dark mb-3">Quyền mở rộng</div>
                <div class="row g-2">
                    @foreach($featurePermissionLabels ?? [] as $ability => $label)
                        <div class="col-md-6">
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="feature_permissions[]"
                                    value="{{ $ability }}"
                                    id="featurePermission{{ \Illuminate\Support\Str::studly($ability) }}"
                                    @checked(in_array($ability, $selectedFeaturePermissions, true))
                                >
                                <label class="form-check-label" for="featurePermission{{ \Illuminate\Support\Str::studly($ability) }}">
                                    {{ $label }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

@if($errors->any())
    <div class="alert alert-danger mt-3 mb-0">
        <div class="fw-bold mb-1">Vui lòng kiểm tra lại thông tin:</div>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-end gap-2 mt-4">
    <a href="{{ route('users.index') }}" class="btn btn-light border fw-bold">Hủy</a>
    <button type="submit" class="btn btn-primary fw-bold">Lưu tài khoản</button>
</div>
