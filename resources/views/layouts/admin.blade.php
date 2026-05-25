<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'WMS - Hệ thống quản lý')</title>

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
        .submenu .sidebar-nested-toggle { padding-left: 1.85rem; font-size: 0.825rem; }
        .submenu .submenu { padding-left: 0.75rem; margin-bottom: 0.25rem; }
        .submenu .submenu .nav-link { padding-left: 2.65rem; font-size: 0.8rem; }

        #content {
            flex-grow: 1; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; transition: all 0.3s;
        }

        .top-navbar {
            background-color: #ffffff; border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 10px 24px; display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 1030;
        }

        .content-wrapper { padding: 24px; flex-grow: 1; }

        #wrapper.toggled #sidebar {
            margin-left: -260px;
        }

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
                        <h1>{{ $systemBrandName }}</h1>
                        <span>Hệ thống quản lý</span>
                    </div>
                </div>
            </div>

            @php
                $navUser = auth()->user();
                $isAdmin = $navUser?->isAdmin();
                $canViewDashboard = $navUser?->canViewOperationsDashboard();
                $canImportStock = $navUser?->canImportStock();
                $canExportStock = $navUser?->canExportStock();
                $canManageUsers = $navUser?->canManageUsers();
                $canManageSettings = $navUser?->canManageSettings();
                $canManageMasterData = $navUser?->canManageMasterData();
                $canViewFinancialReports = $navUser?->canViewFinancialReports();
                $canViewWarehouseReports = $navUser?->canViewWarehouseReports();
                $canViewWarehouseHistory = $navUser?->canViewWarehouseHistory();
                $canTraceSerial = $navUser?->canTraceSerial();
                $canApproveCustomerOrders = $navUser?->canApproveCustomerOrders();
                $canManageDeliveryVehicles = $navUser?->canManageDeliveryVehicles();
                $canManageDeliveryBatches = $navUser?->canManageDeliveryBatches();
                $canViewAllDeliveryBatches = $navUser?->canViewAllDeliveryBatches();
                $canViewDelivery = $canExportStock || $canManageDeliveryBatches || $canViewAllDeliveryBatches;
            @endphp

            <ul class="sidebar-nav" id="sidebarMenu">
                @if($canViewDashboard)
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <div><i class="bi bi-grid-1x2-fill menu-icon"></i>Tổng quan</div>
                        </a>
                    </li>
                @endif

                @if($canManageUsers)
                    <li class="nav-item">
                        <a href="#userSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('users.*') ? 'active' : '' }}" data-sidebar-target="#userSubmenu" role="button" aria-expanded="false" aria-controls="userSubmenu">
                            <div><i class="bi bi-people-fill menu-icon"></i>Quản lý người dùng</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu" id="userSubmenu">
                            <li><a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">Danh sách người dùng</a></li>
                            <li><a href="{{ route('users.create') }}" class="nav-link {{ request()->routeIs('users.create') ? 'active' : '' }}">Tạo tài khoản</a></li>
                        </ul>
                    </li>
                @endif

                @if($canManageSettings)
                    <li class="nav-item">
                        <a href="#systemSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('settings.company.*') ? 'active' : '' }}" data-sidebar-target="#systemSubmenu" role="button" aria-expanded="false" aria-controls="systemSubmenu">
                            <div><i class="bi bi-gear-fill menu-icon"></i>Thiết lập hệ thống</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu" id="systemSubmenu">
                            <li><a href="{{ route('settings.company.edit') }}" class="nav-link {{ request()->routeIs('settings.company.*') ? 'active' : '' }}">Thông tin công ty</a></li>
                        </ul>
                    </li>
                @endif

                @if($isAdmin)
                    <li class="nav-item">
                        <a href="#customerSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('sales.customers.*') || request()->routeIs('sales.customer_accounts.*') ? 'active' : '' }}" data-sidebar-target="#customerSubmenu" role="button" aria-expanded="false" aria-controls="customerSubmenu">
                            <div><i class="bi bi-person-lines-fill menu-icon"></i>Quản lý khách hàng</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu" id="customerSubmenu">
                            <li><a href="{{ route('sales.customers.index') }}" class="nav-link {{ request()->routeIs('sales.customers.*') ? 'active' : '' }}">Khách hàng</a></li>
                            <li><a href="{{ route('sales.customer_accounts.index') }}" class="nav-link {{ request()->routeIs('sales.customer_accounts.*') ? 'active' : '' }}">Tài khoản khách hàng</a></li>
                        </ul>
                    </li>
                @endif

                @if($canManageMasterData)
                    <li class="nav-item">
                        <a href="#productSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('products.index') || request()->routeIs('products.showCatalog') ? 'active' : '' }}" data-sidebar-target="#productSubmenu" role="button" aria-expanded="false" aria-controls="productSubmenu">
                            <div><i class="bi bi-box-seam-fill menu-icon"></i>Quản lý sản phẩm</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu" id="productSubmenu">
                            <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">Danh sách sản phẩm</a></li>
                            {{-- TODO: Hiển thị "Bảng giá" khi có route riêng. --}}
                        </ul>
                    </li>
                @endif

                @if($canImportStock || $canExportStock || $canTraceSerial || $canManageMasterData)
                    @php
                        $warehouseInfoActive = request()->routeIs('product-catalogs.*') || request()->routeIs('suppliers.*') || request()->routeIs('locations.*');
                        $warehouseActive = request()->routeIs('products.import') || request()->routeIs('serial.trace.*') || $warehouseInfoActive;
                    @endphp
                    <li class="nav-item">
                        <a href="#warehouseSubmenu" class="nav-link sidebar-collapse-toggle {{ $warehouseActive ? 'active' : '' }} {{ $warehouseActive ? '' : 'collapsed' }}" data-sidebar-target="#warehouseSubmenu" role="button" aria-expanded="{{ $warehouseActive ? 'true' : 'false' }}" aria-controls="warehouseSubmenu">
                            <div><i class="bi bi-archive-fill menu-icon"></i>Quản lý kho</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu {{ $warehouseActive ? 'show' : '' }}" id="warehouseSubmenu">
                            @if($canImportStock)
                                <li><a href="{{ route('products.import') }}" class="nav-link {{ request()->routeIs('products.import') ? 'active' : '' }}">Nhập kho</a></li>
                            @endif
                            @if($canViewWarehouseReports || $canImportStock || $canExportStock)
                                <li><a href="{{ route('products.index') }}" class="nav-link {{ request()->routeIs('products.index') ? 'active' : '' }}">Tồn kho</a></li>
                            @endif
                            @if($canTraceSerial)
                                <li><a href="{{ route('serial.trace.index') }}" class="nav-link {{ request()->routeIs('serial.trace.*') ? 'active' : '' }}">Truy vết Serial</a></li>
                            @endif
                            @if($canManageMasterData)
                                <li>
                                    <a href="#warehouseInfoSubmenu" class="nav-link sidebar-collapse-toggle sidebar-nested-toggle {{ $warehouseInfoActive ? 'active' : '' }} {{ $warehouseInfoActive ? '' : 'collapsed' }}" data-sidebar-target="#warehouseInfoSubmenu" role="button" aria-expanded="{{ $warehouseInfoActive ? 'true' : 'false' }}" aria-controls="warehouseInfoSubmenu">
                                        <div><i class="bi bi-pencil-square menu-icon"></i>Thay đổi thông tin kho</div>
                                        <i class="bi bi-chevron-down arrow-icon"></i>
                                    </a>
                                    <ul class="collapse submenu {{ $warehouseInfoActive ? 'show' : '' }}" id="warehouseInfoSubmenu">
                                        <li><a href="{{ route('product-catalogs.index') }}" class="nav-link {{ request()->routeIs('product-catalogs.*') ? 'active' : '' }}">Sản phẩm</a></li>
                                        <li><a href="{{ route('suppliers.index') }}" class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">Nhà cung cấp</a></li>
                                        <li><a href="{{ route('locations.index') }}" class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">Vị trí kệ</a></li>
                                    </ul>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if($canExportStock || $canViewDelivery || $canManageDeliveryVehicles)
                    @php($deliveryActive = request()->routeIs('export.*') || request()->routeIs('delivery.batches.*') || request()->routeIs('delivery.vehicles.*'))
                    <li class="nav-item">
                        <a href="#deliverySubmenu" class="nav-link sidebar-collapse-toggle {{ $deliveryActive ? 'active' : '' }} {{ $deliveryActive ? '' : 'collapsed' }}" data-sidebar-target="#deliverySubmenu" role="button" aria-expanded="{{ $deliveryActive ? 'true' : 'false' }}" aria-controls="deliverySubmenu">
                            <div><i class="bi bi-truck menu-icon"></i>Quản lý giao hàng</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu {{ $deliveryActive ? 'show' : '' }}" id="deliverySubmenu">
                            @if($canExportStock)
                                <li><a href="{{ route('export.index') }}" class="nav-link {{ request()->routeIs('export.*') ? 'active' : '' }}">Tạo đơn xuất hàng</a></li>
                            @endif
                            @if($canViewDelivery)
                                <li><a href="{{ route('delivery.batches.index') }}" class="nav-link {{ request()->routeIs('delivery.batches.*') ? 'active' : '' }}">Chuyến giao</a></li>
                            @endif
                            @if($canManageDeliveryVehicles)
                                <li><a href="{{ route('delivery.vehicles.index') }}" class="nav-link {{ request()->routeIs('delivery.vehicles.*') ? 'active' : '' }}">Phương tiện giao hàng</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if($canExportStock || $canApproveCustomerOrders || $isAdmin)
                    <li class="nav-item">
                        <a href="#salesSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('sales.order_approvals.*') ? 'active' : '' }}" data-sidebar-target="#salesSubmenu" role="button" aria-expanded="false" aria-controls="salesSubmenu">
                            <div><i class="bi bi-cart-fill menu-icon"></i>Quản lý bán hàng</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu" id="salesSubmenu">
                            <li><a href="{{ route('shop.index') }}" target="_blank" rel="noopener" class="nav-link">Trang bán hàng</a></li>
                            @if($canApproveCustomerOrders)
                                <li><a href="{{ route('sales.order_approvals.index') }}" class="nav-link {{ request()->routeIs('sales.order_approvals.*') ? 'active' : '' }}">Xác nhận đơn hàng</a></li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if($isAdmin)
                    {{-- TODO: Hiển thị "Quản lý thu chi" khi có route Phiếu thu/Phiếu chi. --}}
                @endif

                @if($canViewFinancialReports || $canViewWarehouseReports || $canViewWarehouseHistory)
                    <li class="nav-item">
                        <a href="#reportSubmenu" class="nav-link collapsed sidebar-collapse-toggle {{ request()->routeIs('reports.*') ? 'active' : '' }}" data-sidebar-target="#reportSubmenu" role="button" aria-expanded="false" aria-controls="reportSubmenu">
                            <div><i class="bi bi-bar-chart-line-fill menu-icon"></i>Báo cáo - Thống kê</div>
                            <i class="bi bi-chevron-down arrow-icon"></i>
                        </a>
                        <ul class="collapse submenu" id="reportSubmenu">
                            @if($canViewFinancialReports)
                                <li><a href="{{ route('reports.revenue') }}" class="nav-link {{ request()->routeIs('reports.revenue') ? 'active' : '' }}">Doanh thu</a></li>
                            @endif
                            @if($canViewWarehouseReports)
                                <li><a href="{{ route('reports.inventory_summary') }}" class="nav-link {{ request()->routeIs('reports.inventory_summary') ? 'active' : '' }}">Nhập xuất tồn</a></li>
                            @endif
                            @if($canViewWarehouseHistory)
                                <li><a href="{{ route('reports.warehouse_history') }}" class="nav-link {{ request()->routeIs('reports.warehouse_history*') ? 'active' : '' }}">Lịch sử kho</a></li>
                            @endif
                        </ul>
                    </li>
                @endif
            </ul>
        </nav>

        <div id="content">
            <nav class="top-navbar">
                <button type="button" id="menu-toggle" class="btn btn-light shadow-sm border-0 bg-white">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-none d-lg-block ms-3 me-auto">
                    <div class="fw-bold text-dark" style="line-height:1;">{{ $systemBrandName }}</div>
                    <span class="text-muted small">Hồ sơ công ty/kho</span>
                </div>

                <div class="d-flex align-items-center gap-3">
                    @auth
                    <a href="{{ route('profile.edit') }}" class="d-flex align-items-center gap-2 text-decoration-none">
                        <div class="text-end d-none d-md-block">
                            <div class="fw-bold fs-6 text-dark" style="line-height: 1;">{{ auth()->user()->displayName() }}</div>
                            <span class="text-muted small">{{ auth()->user()->roleLabel() }}</span>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                            <i class="bi {{ auth()->user()->roleIcon() }}"></i>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary fw-bold" type="submit" title="Đăng xuất">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                    @endauth
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
