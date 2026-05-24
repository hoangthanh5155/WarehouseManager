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
<body class="shop-shell">
@php($shopUser = auth('customer')->user())
@php($isStoreUser = $shopUser?->canSeeAgencyPrice())

<nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shop-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('shop.index') }}">
            <span class="shop-brand-mark bg-primary text-white"><i class="bi bi-bag"></i></span>
            <span>Cửa hàng</span>
        </a>

        <button class="navbar-toggler py-1 px-2 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#shopMobileMenu" aria-controls="shopMobileMenu" aria-label="Mở menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="d-none d-lg-flex flex-grow-1 align-items-center">
            <ul class="navbar-nav me-auto gap-1 ms-3">
                <li class="nav-item"><a class="nav-link shop-nav-link {{ request()->routeIs('shop.index') || request()->routeIs('shop.products.*') ? 'active' : '' }}" href="{{ route('shop.index') }}">Sản phẩm</a></li>
                <li class="nav-item"><a class="nav-link shop-nav-link {{ request()->routeIs('shop.cart') || request()->routeIs('shop.checkout*') ? 'active' : '' }}" href="{{ route('shop.cart') }}"><i class="bi bi-cart me-1"></i>Giỏ hàng</a></li>
                @if($isStoreUser)
                    <li class="nav-item"><a class="nav-link shop-nav-link {{ request()->routeIs('store.*') ? 'active' : '' }}" href="{{ route('store.dashboard') }}">Khu vực cửa hàng</a></li>
                @endif
            </ul>
            <div class="d-flex gap-2">
                @if($shopUser)
                    <a href="{{ route('shop.account') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-circle me-1"></i>{{ $shopUser->name }}</a>
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

<div class="offcanvas offcanvas-end shop-offcanvas d-lg-none" tabindex="-1" id="shopMobileMenu" aria-labelledby="shopMobileMenuLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="shopMobileMenuLabel">Cửa hàng</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Đóng"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="vstack gap-2">
            <a class="shop-mobile-link {{ request()->routeIs('shop.index') || request()->routeIs('shop.products.*') ? 'active' : '' }}" href="{{ route('shop.index') }}">
                <i class="bi bi-grid me-2"></i>Sản phẩm
            </a>
            <a class="shop-mobile-link {{ request()->routeIs('shop.cart') || request()->routeIs('shop.checkout*') ? 'active' : '' }}" href="{{ route('shop.cart') }}">
                <i class="bi bi-cart me-2"></i>Giỏ hàng
            </a>
            @if($isStoreUser)
                <a class="shop-mobile-link {{ request()->routeIs('store.*') ? 'active' : '' }}" href="{{ route('store.dashboard') }}">
                    <i class="bi bi-shop me-2"></i>Khu vực cửa hàng
                </a>
            @endif
        </nav>

        <div class="border-top mt-3 pt-3">
            @if($shopUser)
                <a href="{{ route('shop.account') }}" class="btn btn-outline-primary w-100 mb-2"><i class="bi bi-person-circle me-1"></i>Tài khoản</a>
                <form method="POST" action="{{ route('shop.logout') }}">
                    @csrf
                    <button class="btn btn-outline-secondary w-100">Đăng xuất</button>
                </form>
            @else
                <a href="{{ route('shop.login') }}" class="btn btn-outline-primary w-100 mb-2">Đăng nhập</a>
                <a href="{{ route('shop.register') }}" class="btn btn-primary w-100">Đăng ký</a>
            @endif
        </div>
    </div>
</div>

<main class="container py-3 py-md-4">
    @if(session('success')) <div class="alert alert-success py-2">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger py-2">{{ session('error') }}</div> @endif
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
