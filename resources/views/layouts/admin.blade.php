<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'WMS - Hệ thống quản lý toàn diện')</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        :root {
            --sidebar-bg: #0b1121;
            --sidebar-hover: rgba(255, 255, 255, 0.05);
            --sidebar-text: #8b9bb4;
            --sidebar-text-active: #ffffff;
            --body-bg: #f1f5f9;
            --font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--body-bg);
            overflow-x: hidden;
        }

        #wrapper {
            display: flex;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
        }

        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1040;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar-brand {
            padding: 1.25rem 1.15rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-logo-text {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-brand h1 { font-size: 1.15rem; font-weight: 700; color: #ffffff; margin: 0; }
        .sidebar-brand span { font-size: 0.725rem; color: var(--sidebar-text); display: block; margin-top: 0.25rem; }

        .sidebar-nav { padding: 0.75rem 0; flex-grow: 1; margin: 0; padding-left: 0; }
        .nav-item { list-style: none; margin: 0.125rem 0.75rem; }
        .nav-link {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.65rem 0.85rem; color: var(--sidebar-text);
            font-size: 0.875rem; font-weight: 500; text-decoration: none;
            border-radius: 0.375rem; transition: all 0.2s ease;
        }
        .nav-link:hover, .nav-link.active {
            background-color: var(--sidebar-hover); color: var(--sidebar-text-active);
        }
        .nav-link.active { font-weight: 600; }
        .nav-link div { display: flex; align-items: center; }
        .nav-link i.menu-icon { font-size: 1.1rem; margin-right: 0.75rem; width: 20px; text-align: center; }
        
        .arrow-icon { font-size: 0.75rem; transition: transform 0.2s ease; }
        .nav-link:not(.collapsed) .arrow-icon { transform: rotate(180deg); color: var(--sidebar-text-active); }

        .submenu { padding-left: 1rem; list-style: none; margin-bottom: 0.5rem; }
        .submenu .nav-link { padding: 0.45rem 0.85rem 0.45rem 1.85rem; font-size: 0.825rem; font-weight: 400; }

        #content {
            flex-grow: 1; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; transition: all 0.3s;
        }

        .top-navbar {
            background-color: #ffffff; border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 10px 24px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 1030;
        }

        .content-wrapper { padding: 24px; flex-grow: 1; }

        /* --- TOGGLE STATE --- */
        #wrapper.toggled #sidebar {
            margin-left: -260px;
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            #sidebar {
                position: fixed;
                height: 100vh;
                margin-left: -260px;
            }
            #wrapper.toggled #sidebar {
                margin-left: 0 !important;
            }
            .content-wrapper { padding: 15px; }
        }
    </style>
    
    @stack('styles')
</head>
<body>

    <div id="wrapper">
        <nav id="sidebar">
            <div class="sidebar-brand">
                <div class="brand-logo-text">
                    <div class="bg-primary rounded p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold fs-5">P</span>
                    </div>
                    <div>
                        <h1>WMS</h1>
                        <span>Hệ thống quản lý</span>
                    </div>
                </div>
            </div>
            
            <ul class="sidebar-nav" id="sidebarMenu">
                <li class="nav-item">
                    <a href="{{ url('/dashboard') }}" class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}">
                        <div><i class="bi bi-grid-1x2-fill menu-icon"></i>Tổng quan</div>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#userSubmenu" class="nav-link collapsed sidebar-collapse-toggle" data-sidebar-target="#userSubmenu" role="button" aria-expanded="false" aria-controls="userSubmenu">
                        <div><i class="bi bi-people-fill menu-icon"></i>Quản lý người dùng</div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </a>
                    <ul class="collapse submenu" id="userSubmenu">
                        <li><a href="#" class="nav-link">Danh sách user</a></li>
                        <li><a href="#" class="nav-link">Phân quyền</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div><i class="bi bi-gear-fill menu-icon"></i>Thiết lập hệ thống</div>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div><i class="bi bi-person-lines-fill menu-icon"></i>Quản lý khách hàng</div>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#productSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('products.index') || request()->routeIs('products.showCatalog') ? 'active' : '' }}" data-sidebar-target="#productSubmenu" role="button" aria-expanded="false" aria-controls="productSubmenu">
                        <div><i class="bi bi-box-seam-fill menu-icon"></i>Quản lý sản phẩm</div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </a>
                    <ul class="collapse submenu" id="productSubmenu">
                        <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">Danh sách sản phẩm</a></li>
                        <li><a href="#" class="nav-link">Danh mục</a></li>
                        <li><a href="#" class="nav-link">Bảng giá</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#warehouseSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->is('import*') || request()->is('product-catalogs*') || request()->is('suppliers*') || request()->is('locations*') ? 'active' : '' }}" data-sidebar-target="#warehouseSubmenu" role="button" aria-expanded="false" aria-controls="warehouseSubmenu">
                        <div><i class="bi bi-archive-fill menu-icon"></i>Quản lý kho</div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </a>
                    <ul class="collapse submenu" id="warehouseSubmenu">
                        <li><a href="{{ url('/import') }}" class="nav-link {{ request()->is('import') ? 'active' : '' }}">Nhập kho</a></li>
                        <li><a href="{{ route('export.index') }}" class="nav-link {{ request()->routeIs('export.*') ? 'active' : '' }}">Xuất kho</a></li>
                        <li><a href="#" class="nav-link">Truy vết Serial</a></li>
                        <li><a href="#" class="nav-link">Tồn kho</a></li>
                        
                        <li class="nav-item">
                            <a href="#changeInfoSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->is('product-catalogs*') || request()->is('suppliers*') || request()->is('locations*') ? 'active' : '' }}" data-sidebar-target="#changeInfoSubmenu" role="button" aria-expanded="false" aria-controls="changeInfoSubmenu" style="padding-left: 1.85rem; font-size: 0.85rem;">
                                <div><i class="bi bi-pencil-square menu-icon" style="font-size: 1rem;"></i>Thay đổi thông tin kho</div>
                                <i class="bi bi-chevron-down arrow-icon" style="font-size: 0.65rem;"></i>
                            </a>
                            <ul class="collapse submenu" id="changeInfoSubmenu">
                                <li>
                                    <a href="{{ url('/product-catalogs') }}" class="nav-link {{ request()->is('product-catalogs*') ? 'active' : '' }}" style="padding-left: 2.85rem; font-size: 0.8rem;">
                                        <i class="bi bi-dot me-1"></i>Sản phẩm
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ url('/suppliers') }}" class="nav-link {{ request()->is('suppliers*') ? 'active' : '' }}" style="padding-left: 2.85rem; font-size: 0.8rem;">
                                        <i class="bi bi-dot me-1"></i>Nhà cung cấp
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ url('/locations') }}" class="nav-link {{ request()->is('locations*') ? 'active' : '' }}" style="padding-left: 2.85rem; font-size: 0.8rem;">
                                        <i class="bi bi-dot me-1"></i>Vị trí kệ
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#financeSubmenu" class="nav-link collapsed sidebar-collapse-toggle" data-sidebar-target="#financeSubmenu" role="button" aria-expanded="false" aria-controls="financeSubmenu">
                        <div><i class="bi bi-cash-stack menu-icon"></i>Quản lý thu chi</div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </a>
                    <ul class="collapse submenu" id="financeSubmenu">
                        <li><a href="#" class="nav-link">Phiếu thu</a></li>
                        <li><a href="#" class="nav-link">Phiếu chi</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <div><i class="bi bi-cart-fill menu-icon"></i>Quản lý bán hàng</div>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="#reportSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-sidebar-target="#reportSubmenu" role="button" aria-expanded="false" aria-controls="reportSubmenu">
                        <div><i class="bi bi-bar-chart-line-fill menu-icon"></i>Báo cáo - Thống kê</div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </a>
                    <ul class="collapse submenu" id="reportSubmenu">
                        <li><a href="{{ route('reports.revenue') }}" class="nav-link {{ request()->routeIs('reports.revenue') ? 'active' : '' }}">Doanh thu</a></li>
                        <li><a href="#" class="nav-link">Nhập xuất tồn</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a href="#manufactureSubmenu" class="nav-link collapsed sidebar-collapse-toggle" data-sidebar-target="#manufactureSubmenu" role="button" aria-expanded="false" aria-controls="manufactureSubmenu">
                        <div><i class="bi bi-building-fill menu-icon"></i>Quản lý sản xuất</div>
                        <i class="bi bi-chevron-down arrow-icon"></i>
                    </a>
                    <ul class="collapse submenu" id="manufactureSubmenu">
                        <li><a href="#" class="nav-link">Lệnh sản xuất</a></li>
                        <li><a href="#" class="nav-link">Tiến độ</a></li>
                    </ul>
                </li>
            </ul>
        </nav>

        <div id="content">
            <nav class="top-navbar">
                <button type="button" id="menu-toggle" class="btn btn-light shadow-sm border-0 bg-white">
                    <i class="bi bi-list fs-5"></i>
                </button>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="text-end d-none d-md-block">
                        <div class="fw-bold fs-6 text-dark" style="line-height: 1;">System Admin</div>
                        <span class="badge bg-success mt-1 px-2" style="font-size: 0.7rem;">Online</span>
                    </div>
                    <img src="https://ui-avatars.com/api/?name=Admin&background=0b1121&color=fff&rounded=true" alt="User" class="rounded-circle shadow-sm" width="42" height="42">
                </div>
            </nav>

            <div class="content-wrapper">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @vite(['resources/js/app.js'])
    
    @stack('scripts')
</body>
</html>
