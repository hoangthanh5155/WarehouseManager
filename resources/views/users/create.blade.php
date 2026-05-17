@extends('layouts.admin')

@section('title', 'Tạo người dùng')

@section('content')
<div class="container-fluid px-1 px-md-2 mb-5" style="max-width: 980px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Tạo người dùng nội bộ</h3>
            <div class="text-muted">Tạo tài khoản cho nhân sự trong kho.</div>
        </div>
    </div>

    @if(session('created_account'))
        @php($createdAccount = session('created_account'))
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <div class="bg-success-subtle text-success rounded-3 d-grid flex-shrink-0" style="width:44px;height:44px;place-items:center;">
                        <i class="bi bi-check-circle fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold text-dark mb-1">Tài khoản đã được tạo thành công</h5>
                        <div class="text-muted small">Chỉ chia sẻ mật khẩu này cho đúng người dùng.</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="bg-light border rounded-3 p-3 h-100">
                            <div class="small fw-bold text-muted mb-1">Tên đăng nhập</div>
                            <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-between">
                                <div class="fw-bold text-dark text-break">{{ $createdAccount['username'] }}</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-copy-value="{{ $createdAccount['username'] }}">
                                    <i class="bi bi-clipboard me-1"></i><span>Sao chép</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light border rounded-3 p-3 h-100">
                            <div class="small fw-bold text-muted mb-1">Mật khẩu tạm</div>
                            <div class="d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-between">
                                <div class="fw-bold text-dark text-break">{{ $createdAccount['temporary_password'] }}</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-bold" data-copy-value="{{ $createdAccount['temporary_password'] }}">
                                    <i class="bi bi-clipboard me-1"></i><span>Sao chép</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light border rounded-3 p-3 h-100">
                            <div class="small fw-bold text-muted mb-1">Vai trò</div>
                            <div class="fw-bold text-dark">{{ $createdAccount['role_label'] }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-light border rounded-3 p-3 h-100">
                            <div class="small fw-bold text-muted mb-1">Ghi chú</div>
                            <div class="text-muted small">Đây là mật khẩu tạm. Người dùng sẽ phải đổi mật khẩu sau lần đăng nhập đầu tiên.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-3 p-md-4">
            <form method="POST" action="{{ route('users.store') }}">
                @include('users._form')
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const usernameInput = document.getElementById('userNameInput');
    const passwordPreview = document.getElementById('initialPasswordPreview');
    const regenerateButton = document.getElementById('regenerateInitialPassword');

    function randomFourDigits() {
        return String(Math.floor(1000 + Math.random() * 9000));
    }

    function generateInitialPassword() {
        if (!usernameInput || !passwordPreview) return;

        const username = usernameInput.value.trim();
        passwordPreview.value = username ? `WMS@${username}#${randomFourDigits()}` : '';
    }

    if (usernameInput && passwordPreview) {
        generateInitialPassword();
        usernameInput.addEventListener('input', generateInitialPassword);
    }

    if (regenerateButton) {
        regenerateButton.addEventListener('click', generateInitialPassword);
    }

    function fallbackCopy(value) {
        const input = document.createElement('input');
        input.value = value;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }

    document.querySelectorAll('[data-copy-value]').forEach(button => {
        button.addEventListener('click', async function () {
            const value = this.getAttribute('data-copy-value') || '';
            const icon = this.querySelector('i');
            const label = this.querySelector('span');
            const originalIcon = icon ? icon.className : '';
            const originalText = label ? label.textContent : '';

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(value);
                } else {
                    fallbackCopy(value);
                }

                if (icon) icon.className = 'bi bi-check2 me-1';
                if (label) label.textContent = 'Đã sao chép';

                setTimeout(() => {
                    if (icon) icon.className = originalIcon;
                    if (label) label.textContent = originalText || 'Sao chép';
                }, 1500);
            } catch (error) {
                fallbackCopy(value);
            }
        });
    });
});
</script>
@endpush
