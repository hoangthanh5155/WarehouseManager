<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cửa hàng')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-light">
@php($shopUser = auth('customer')->user())
<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('shop.index') }}">Cửa hàng</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#shopNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="shopNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="{{ route('shop.index') }}">Sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('shop.cart') }}">Giỏ hàng</a></li>
            </ul>
            <div class="d-flex gap-2">
                @if($shopUser)
                    <a href="{{ route('shop.account') }}" class="btn btn-outline-primary btn-sm">{{ $shopUser->name }}</a>
                    <form method="POST" action="{{ route('shop.logout') }}">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm">Đăng xuất</button>
                    </form>
                @else
                    <a href="{{ route('shop.login') }}" class="btn btn-outline-primary btn-sm">Đăng nhập</a>
                    <a href="{{ route('shop.register') }}" class="btn btn-primary btn-sm">Đăng ký</a>
                @endif
            </div>
        </div>
    </div>
</nav>

<main class="container py-4">
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
