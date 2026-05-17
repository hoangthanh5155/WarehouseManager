<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: #f1f5f9; display: grid; place-items: center; font-family: Inter, system-ui, sans-serif; }
        .auth-card { width: min(440px, calc(100vw - 28px)); border: 0; border-radius: 18px; box-shadow: 0 20px 54px rgba(15, 23, 42, .12); }
    </style>
</head>
<body>
    <main class="card auth-card">
        <div class="card-body p-4 p-md-5">
            <h1 class="h4 fw-bold mb-1">Đặt lại mật khẩu</h1>
            <div class="text-muted small mb-4">Không cần mật khẩu hiện tại khi dùng liên kết đặt lại.</div>

            @if($errors->any())
                <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Email</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" class="form-control form-control-lg" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control form-control-lg" required autocomplete="new-password">
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted">Nhập lại mật khẩu mới</label>
                    <input type="password" name="password_confirmation" class="form-control form-control-lg" required autocomplete="new-password">
                </div>

                <button class="btn btn-primary btn-lg w-100 fw-bold" type="submit">Đặt lại mật khẩu</button>
            </form>
        </div>
    </main>
</body>
</html>
