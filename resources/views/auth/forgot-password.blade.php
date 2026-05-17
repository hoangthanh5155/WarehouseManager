<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { min-height: 100vh; background: #f1f5f9; display: grid; place-items: center; font-family: Inter, system-ui, sans-serif; }
        .auth-card { width: min(420px, calc(100vw - 28px)); border: 0; border-radius: 18px; box-shadow: 0 20px 54px rgba(15, 23, 42, .12); }
    </style>
</head>
<body>
    <main class="card auth-card">
        <div class="card-body p-4 p-md-5">
            <div class="mb-4">
                <h1 class="h4 fw-bold m-0">Quên mật khẩu</h1>
                <div class="text-muted small">Ghi nhận yêu cầu đặt lại mật khẩu nội bộ.</div>
            </div>

            @if(session('status'))
                <div class="alert alert-success py-2">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control form-control-lg" required autofocus>
                    @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary btn-lg w-100 fw-bold" type="submit">Ghi nhận yêu cầu</button>
            </form>

            <a href="{{ route('login') }}" class="btn btn-link w-100 mt-3 text-decoration-none">Quay lại đăng nhập</a>
        </div>
    </main>
</body>
</html>
