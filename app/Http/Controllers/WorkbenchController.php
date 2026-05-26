<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkbenchController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $groups = [
            [
                'key' => 'overview',
                'title' => 'Tổng quan hôm nay',
                'description' => 'Theo dõi số liệu và việc cần xử lý.',
                'icon' => 'bi-speedometer2',
                'actions' => [
                    [
                        'label' => 'Tổng quan hệ thống',
                        'description' => 'Mở màn hình tổng quan hiện có.',
                        'icon' => 'bi-grid-1x2-fill',
                        'route' => route('dashboard'),
                        'badge' => null,
                        'visible' => $user?->canViewOperationsDashboard(),
                    ],
                ],
            ],
            [
                'key' => 'warehouse',
                'title' => 'Kho',
                'description' => 'Nhập kho, tồn kho, truy vết serial.',
                'icon' => 'bi-archive-fill',
                'actions' => [
                    [
                        'label' => 'Nhập kho',
                        'description' => 'Tạo phiếu nhập và đưa hàng vào kho.',
                        'icon' => 'bi-box-arrow-in-down',
                        'route' => route('products.import'),
                        'badge' => null,
                        'visible' => $user?->canImportStock(),
                    ],
                    [
                        'label' => 'Tồn kho',
                        'description' => 'Xem danh sách sản phẩm và tồn hiện có.',
                        'icon' => 'bi-box-seam',
                        'route' => route('products.index'),
                        'badge' => null,
                        'visible' => $user?->canViewWarehouseReports()
                            || $user?->canImportStock()
                            || $user?->canExportStock(),
                    ],
                    [
                        'label' => 'Truy vết Serial',
                        'description' => 'Tra cứu lịch sử theo Serial Number.',
                        'icon' => 'bi-upc-scan',
                        'route' => route('serial.trace.index'),
                        'badge' => null,
                        'visible' => $user?->canTraceSerial(),
                    ],
                ],
            ],
            [
                'key' => 'orders',
                'title' => 'Đơn & xuất hàng',
                'description' => 'Tạo đơn xuất hàng, xác nhận đơn hàng.',
                'icon' => 'bi-receipt',
                'actions' => [
                    [
                        'label' => 'Tạo đơn xuất hàng',
                        'description' => 'Tạo đơn cần giao, không xử lý serial tại bước này.',
                        'icon' => 'bi-file-earmark-plus',
                        'route' => route('export.index'),
                        'badge' => null,
                        'visible' => $user?->canExportStock(),
                    ],
                    [
                        'label' => 'Xác nhận đơn hàng',
                        'description' => 'Duyệt các đơn khách hàng đã đặt.',
                        'icon' => 'bi-clipboard-check',
                        'route' => route('sales.order_approvals.index'),
                        'badge' => null,
                        'visible' => $user?->canApproveCustomerOrders(),
                    ],
                ],
            ],
            [
                'key' => 'delivery',
                'title' => 'Giao hàng',
                'description' => 'Chuyến giao, xử lý giao hàng, phương tiện giao hàng.',
                'icon' => 'bi-truck',
                'actions' => [
                    [
                        'label' => 'Chuyến giao',
                        'description' => 'Mở danh sách chuyến giao hiện có.',
                        'icon' => 'bi-signpost-2',
                        'route' => route('delivery.batches.index'),
                        'badge' => null,
                        'visible' => $user?->canExportStock()
                            || $user?->canManageDeliveryBatches()
                            || $user?->canViewAllDeliveryBatches(),
                    ],
                    [
                        'label' => 'Xử lý giao hàng',
                        'description' => 'Mở danh sách đơn giao hiện có.',
                        'icon' => 'bi-bag-check',
                        'route' => route('delivery.orders.index'),
                        'badge' => null,
                        'visible' => $user?->canExportStock()
                            || $user?->canManageDeliveryBatches()
                            || $user?->canViewAllDeliveryBatches(),
                    ],
                    [
                        'label' => 'Phương tiện giao hàng',
                        'description' => 'Quản lý xe máy, ô tô dùng cho chuyến giao.',
                        'icon' => 'bi-truck-front',
                        'route' => route('delivery.vehicles.index'),
                        'badge' => null,
                        'visible' => $user?->canManageDeliveryVehicles(),
                    ],
                ],
            ],
            [
                'key' => 'reports',
                'title' => 'Báo cáo',
                'description' => 'Doanh thu, nhập xuất tồn, lịch sử kho.',
                'icon' => 'bi-bar-chart-line',
                'actions' => [
                    [
                        'label' => 'Doanh thu',
                        'description' => 'Xem báo cáo doanh thu hiện có.',
                        'icon' => 'bi-cash-stack',
                        'route' => route('reports.revenue'),
                        'badge' => null,
                        'visible' => $user?->canViewFinancialReports(),
                    ],
                    [
                        'label' => 'Nhập xuất tồn',
                        'description' => 'Xem báo cáo tồn kho tổng hợp.',
                        'icon' => 'bi-boxes',
                        'route' => route('reports.inventory_summary'),
                        'badge' => null,
                        'visible' => $user?->canViewWarehouseReports(),
                    ],
                    [
                        'label' => 'Lịch sử kho',
                        'description' => 'Xem lịch sử nhập xuất và biến động kho.',
                        'icon' => 'bi-clock-history',
                        'route' => route('reports.warehouse_history'),
                        'badge' => null,
                        'visible' => $user?->canViewWarehouseHistory(),
                    ],
                ],
            ],
        ];

        $groups = collect($groups)
            ->map(function (array $group) {
                $group['actions'] = collect($group['actions'])
                    ->filter(fn (array $action) => (bool) ($action['visible'] ?? false))
                    ->values()
                    ->all();

                return $group;
            })
            ->filter(fn (array $group) => count($group['actions']) > 0)
            ->values()
            ->all();

        $selectedGroupKey = $request->query('group');
        $selectedGroup = collect($groups)->firstWhere('key', $selectedGroupKey);

        return view('workbench.index', compact('groups', 'selectedGroup'));
    }
}
