<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsWithApi;
use App\Models\Customer;
use App\Models\DeliveryBatchSerial;
use App\Models\ExportVoucher;
use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderSerial;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Services\Warehouse\FulfillmentPreparationService;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExportController extends Controller
{
    use RespondsWithApi;

    public function index()
    {
        try {
            $customers = Customer::orderBy('name', 'asc')->get();

            $productCatalogs = ProductCatalog::withCount(['products' => function ($query) {
                $query->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
                    ->whereDoesntHave('activeFulfillmentReservation')
                    ->whereDoesntHave('activeDeliveryReservation');
            }])
            ->orderBy('product_name', 'asc')
            ->get();

            $systemOrders = FulfillmentOrder::query()
                ->whereIn('status', [
                    WarehouseConstants::FULFILLMENT_PENDING,
                    WarehouseConstants::FULFILLMENT_PENDING_PREPARE,
                ])
                ->whereIn('order_type', [
                    WarehouseConstants::ORDER_TYPE_SYSTEM,
                    WarehouseConstants::ORDER_TYPE_GUEST,
                ])
                ->with('items.productCatalog')
                ->withSum('items as total_amount', 'total_amount')
                ->latest()
                ->limit(100)
                ->get();

            $systemOrdersPayload = $systemOrders->map(fn ($order) => [
                'id' => $order->id,
                'order_code' => $order->order_code,
                'buyer_name' => $order->buyer_name,
                'company_name' => $order->company_name,
                'address' => $order->address,
                'tax_code' => $order->tax_code,
                'customer_type' => $order->customer_type,
                'total_amount' => (float) ($order->total_amount ?? 0),
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_catalog_id' => $item->product_catalog_id,
                    'product_name' => $item->product_name_snapshot ?: ($item->productCatalog->product_name ?? 'N/A'),
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                ])->values()->all(),
            ])->values()->all();

            $recentVouchers = ExportVoucher::orderByDesc('exported_at')
                ->limit(8)
                ->get();

            return view('exports.create', compact('customers', 'productCatalogs', 'recentVouchers', 'systemOrders', 'systemOrdersPayload'));
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loi hien thi giao dien: ' . $e->getMessage() . ' tai dong ' . $e->getLine(),
            ], 500);
        }
    }

    public function checkSerial(Request $request, $serial_number)
    {
        try {
            $productId = $request->query('product_id');

            $query = Product::query()
                ->with('productCatalog', 'location')
                ->where('serial_number', $serial_number)
                ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK);

            if ($productId) {
                $query->where('product_catalog_id', $productId);
            }

            $item = $query->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => $productId ? 'SN sai sản phẩm.' : 'SN không có trong kho.',
                ], 404);
            }

            $reserved = FulfillmentOrderSerial::query()
                ->where('active_product_id', $item->id)
                ->exists();

            $batchReserved = DeliveryBatchSerial::query()
                ->where('active_product_id', $item->id)
                ->exists();

            if ($reserved || $batchReserved) {
                return response()->json([
                    'success' => false,
                    'message' => 'SN đang được giữ cho đơn khác.',
                ], 422);
            }

            $catalog = $item->productCatalog;

            $payload = [
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'product_id' => $item->id,
                    'product_catalog_id' => $item->product_catalog_id,
                    'serial_number' => $item->serial_number,
                    'product_name' => $catalog?->product_name,
                    'retail_price' => (float) ($catalog?->retail_price ?? 0),
                    'agency_price' => (float) ($catalog?->agency_price ?? 0),
                    'location_name' => $item->location?->shelf_name,
                ],
            ];

            if ($request->user()?->canViewCostPrices()) {
                $payload['data']['wholesale_price'] = $catalog ? (float) $catalog->wholesale_price : 0;
            }

            return response()->json($payload);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loi kiem tra Serial: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, FulfillmentPreparationService $preparationService)
    {
        $validator = Validator::make($request->all(), [
            'export_type' => 'required|string',
            'customer_type' => 'nullable|string',
            'buyer_name' => 'nullable|string',
            'fulfillment_order_id' => ['required_if:export_type,' . WarehouseConstants::EXPORT_SYSTEM, 'nullable', 'integer', 'exists:fulfillment_orders,id'],
            'serials' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu khong hop le.', $validator->errors(), 422);
        }

        try {
            $payload = $validator->validated() + $request->all();
            if (($payload['export_type'] ?? null) === WarehouseConstants::EXPORT_SYSTEM) {
                $order = FulfillmentOrder::query()->findOrFail((int) $payload['fulfillment_order_id']);
                $order = $preparationService->prepareSystemOrder($order, $payload['serials'], $request->user()?->id);
            } else {
                $order = $preparationService->prepareNormal($payload, $request->user()?->id);
            }

            return $this->successResponse('Đã lưu chờ giao.', [
                'fulfillment_order_id' => $order->id,
                'order_code' => $order->order_code,
                'print_url' => route('delivery.orders.print', $order),
                'public_url' => route('delivery.orders.public', $order->public_token),
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponse('Dữ liệu soạn hàng không hợp lệ.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Lỗi xử lý soạn hàng: ' . $e->getMessage(), [], 500);
        }
    }

    public function print($id)
    {
        try {
            $voucher = ExportVoucher::findOrFail($id);

            $subVouchers = [];
            if ($voucher->parent_id === null) {
                $subVouchers = ExportVoucher::where('parent_id', $voucher->id)->get();
            }

            return view('exports.print', compact('voucher', 'subVouchers'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Khong tim thay hoa don can in: ' . $e->getMessage());
        }
    }

    public function updateMetadata(Request $request, ExportVoucher $voucher)
    {
        $validated = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:255'],
        ]);

        $voucher->update($validated);

        return redirect()->route('export.index')->with('success', 'Da cap nhat thong tin hoa don.');
    }
}
