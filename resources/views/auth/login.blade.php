<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { min-height: 100vh; background: #f1f5f9; display: grid; place-items: center; font-family: Inter, system-ui, sans-serif; }
        .login-card { width: min(420px, calc(100vw - 28px)); border: 0; border-radius: 18px; box-shadow: 0 20px 54px rgba(15, 23, 42, .12); }
        .login-icon { width: 52px; height: 52px; border-radius: 16px; display: grid; place-items: center; background: #0d6efd; color: #fff; font-size: 1.35rem; }
    </style>
</head>
<body>
    <main class="card login-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="login-icon"><i class="bi bi-shield-lock"></i></div>
                <div>
                    <h1 class="h4 fw-bold m-0">Đăng nhập quản trị</h1>
                    <div class="text-muted small">{{ $systemBrandName }}</div>
                </div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2">
                    {{ $errors->first() }}
                </div>
            @endif
            @if(session('status'))
                <div class="alert alert-success py-2">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Tên đăng nhập hoặc Email</label>
                    <input type="text" name="login" value="{{ old('login') }}" class="form-control form-control-lg" autocomplete="username" required autofocus>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Mật khẩu</label>
                    <input type="password" name="password" class="form-control form-control-lg" autocomplete="current-password" required>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button class="btn btn-primary btn-lg w-100 fw-bold" type="submit">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Đăng nhập
                </button>
            </form>
            <a href="{{ route('password.request') }}" class="btn btn-link w-100 mt-3 text-decoration-none">Quên mật khẩu?</a>
        </div>
    </main>
</body>
</html>
