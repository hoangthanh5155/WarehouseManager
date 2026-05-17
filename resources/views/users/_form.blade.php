@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Tên đăng ký</label>
        <input type="text" name="name" value="{{ old('name', $managedUser->name ?? '') }}" class="form-control" required>
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
        <select name="role" class="form-select" required>
            @foreach($manageableRoles as $role => $label)
                <option value="{{ $role }}" @selected(old('role', $managedUser->role ?? '') === $role)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-bold small text-muted">Trạng thái</label>
        <select name="status" class="form-select" required>
            @foreach($statusLabels as $status => $label)
                <option value="{{ $status }}" @selected(old('status', $managedUser->status ?? 'active') === $status)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @unless(isset($managedUser))
        <div class="col-md-6">
            <label class="form-label fw-bold small text-muted">Mật khẩu</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold small text-muted">Nhập lại mật khẩu</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
    @endunless
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
