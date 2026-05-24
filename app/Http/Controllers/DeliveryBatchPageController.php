<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBatch;
use App\Models\FulfillmentOrder;
use App\Models\FulfillmentOrderSerial;
use App\Services\Warehouse\ExportStockService;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryBatchPageController extends Controller
{
    public function ordersIndex()
    {
        $orders = FulfillmentOrder::query()
            ->whereIn('status', [
                WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
                WarehouseConstants::FULFILLMENT_IN_DELIVERY,
                WarehouseConstants::FULFILLMENT_DELIVERED,
                WarehouseConstants::FULFILLMENT_FAILED,
            ])
            ->with(['items.productCatalog', 'preparedSerials.productCatalog'])
            ->withCount('items')
            ->withCount(['preparedSerials as prepared_serials_count' => fn ($query) => $query->where('status', WarehouseConstants::ORDER_SERIAL_PREPARED)])
            ->withSum('items as total_quantity', 'quantity')
            ->withSum('items as total_amount', 'total_amount')
            ->latest()
            ->paginate(15);

        return view('delivery.orders.index', compact('orders'));
    }

    public function batchesIndex()
    {
        $batches = DeliveryBatch::query()
            ->withCount(['batchOrders', 'serials'])
            ->latest()
            ->paginate(15);

        return view('delivery.batches.index', compact('batches'));
    }

    public function batchesShow(DeliveryBatch $deliveryBatch)
    {
        $deliveryBatch->load([
            'batchOrders.fulfillmentOrder.items.productCatalog',
            'batchOrders.fulfillmentOrder.preparedSerials.productCatalog',
        ]);

        $availableOrders = FulfillmentOrder::query()
            ->where('status', WarehouseConstants::FULFILLMENT_READY_TO_DELIVER)
            ->whereDoesntHave('batchOrders', fn ($query) => $query->where('delivery_batch_id', $deliveryBatch->id))
            ->withSum('items as total_quantity', 'quantity')
            ->latest()
            ->limit(50)
            ->get();

        return view('delivery.batches.show', [
            'batch' => $deliveryBatch,
            'availableOrders' => $availableOrders,
        ]);
    }

    public function print(FulfillmentOrder $fulfillmentOrder)
    {
        $fulfillmentOrder->load(['items.productCatalog', 'preparedSerials.productCatalog']);

        if (!$fulfillmentOrder->printed_at) {
            $fulfillmentOrder->update(['printed_at' => now()]);
        }

        return view('delivery.orders.print', [
            'order' => $fulfillmentOrder,
            'publicView' => false,
        ]);
    }

    public function publicSlip(string $token)
    {
        $order = FulfillmentOrder::query()
            ->where('public_token', $token)
            ->with(['items.productCatalog', 'preparedSerials.productCatalog'])
            ->firstOrFail();

        return view('delivery.orders.print', [
            'order' => $order,
            'publicView' => true,
        ]);
    }

    public function deliver(Request $request, FulfillmentOrder $fulfillmentOrder, ExportStockService $exportStockService)
    {
        $validated = $request->validate([
            'serials' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($validated, $fulfillmentOrder, $exportStockService, $request) {
                $order = FulfillmentOrder::query()
                    ->whereKey($fulfillmentOrder->id)
                    ->with('items')
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($order->status, [
                    WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
                    WarehouseConstants::FULFILLMENT_IN_DELIVERY,
                ], true)) {
                    throw ValidationException::withMessages(['order' => 'Đơn chưa sẵn sàng giao.']);
                }

                $scannedSerials = collect(preg_split('/\R+/', (string) $validated['serials']))
                    ->map(fn ($serial) => trim($serial))
                    ->filter()
                    ->values();

                if ($scannedSerials->isEmpty() || $scannedSerials->count() !== $scannedSerials->unique()->count()) {
                    throw ValidationException::withMessages(['serials' => 'SN xác nhận không hợp lệ.']);
                }

                $preparedSerials = FulfillmentOrderSerial::query()
                    ->where('fulfillment_order_id', $order->id)
                    ->where('status', WarehouseConstants::ORDER_SERIAL_PREPARED)
                    ->orderBy('serial_number_snapshot')
                    ->lockForUpdate()
                    ->get();

                $expectedSerials = $preparedSerials->pluck('serial_number_snapshot')->sort()->values();
                $actualSerials = $scannedSerials->sort()->values();
                if ($expectedSerials->implode('|') !== $actualSerials->implode('|')) {
                    throw ValidationException::withMessages(['serials' => 'SN xác nhận không khớp đơn.']);
                }

                foreach ($order->items as $item) {
                    $count = $preparedSerials->where('fulfillment_order_item_id', $item->id)->count();
                    if ($count !== (int) $item->quantity) {
                        throw ValidationException::withMessages(['serials' => 'Số SN không khớp ' . $item->product_name_snapshot . '.']);
                    }
                }

                $exportItems = $order->items->map(function ($item) use ($preparedSerials) {
                    return [
                        'product_catalog_id' => $item->product_catalog_id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'serials' => $preparedSerials
                            ->where('fulfillment_order_item_id', $item->id)
                            ->pluck('serial_number_snapshot')
                            ->values()
                            ->all(),
                    ];
                })->values()->all();

                $result = $exportStockService->export([
                    'export_type' => WarehouseConstants::EXPORT_NORMAL,
                    'customer_type' => $order->customer_type,
                    'customer_id' => $order->customer_id,
                    'buyer_name' => $order->buyer_name,
                    'company_name' => $order->company_name,
                    'address' => $order->address,
                    'tax_code' => $order->tax_code,
                    'note' => $validated['note'] ?? ('Fulfillment order ' . $order->order_code),
                    'main_items' => $exportItems,
                ], $request->user()?->id);

                $now = now();
                FulfillmentOrderSerial::query()
                    ->whereIn('id', $preparedSerials->pluck('id'))
                    ->update([
                        'status' => WarehouseConstants::ORDER_SERIAL_DELIVERED,
                        'active_product_id' => null,
                        'delivered_at' => $now,
                    ]);

                $order->update([
                    'status' => WarehouseConstants::FULFILLMENT_DELIVERED,
                    'delivered_by' => $request->user()?->id,
                    'delivered_at' => $now,
                    'export_voucher_id' => $result['export_voucher_id'],
                ]);

                $order->batchOrders()->whereNotIn('status', [
                    WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                    WarehouseConstants::DELIVERY_ORDER_FAILED,
                    WarehouseConstants::DELIVERY_ORDER_CANCELLED,
                ])->update([
                    'status' => WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                    'delivered_at' => $now,
                ]);
            });

            return redirect()->route('delivery.orders.index')->with('success', 'Đã xác nhận giao hàng.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function fail(Request $request, FulfillmentOrder $fulfillmentOrder)
    {
        $validated = $request->validate([
            'failure_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($fulfillmentOrder, $validated, $request) {
            $order = FulfillmentOrder::query()
                ->whereKey($fulfillmentOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();
            FulfillmentOrderSerial::query()
                ->where('fulfillment_order_id', $order->id)
                ->where('status', WarehouseConstants::ORDER_SERIAL_PREPARED)
                ->lockForUpdate()
                ->update([
                    'status' => WarehouseConstants::ORDER_SERIAL_RELEASED,
                    'active_product_id' => null,
                    'released_at' => $now,
                ]);

            $order->update([
                'status' => WarehouseConstants::FULFILLMENT_FAILED,
                'failed_at' => $now,
                'failure_reason' => $validated['failure_reason'] ?? null,
            ]);

            $order->batchOrders()->whereNotIn('status', [
                WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                WarehouseConstants::DELIVERY_ORDER_FAILED,
                WarehouseConstants::DELIVERY_ORDER_CANCELLED,
            ])->update([
                'status' => WarehouseConstants::DELIVERY_ORDER_FAILED,
                'failed_at' => $now,
                'note' => $validated['failure_reason'] ?? null,
            ]);
        });

        return redirect()->route('delivery.orders.index')->with('success', 'Đã đánh dấu giao thất bại.');
    }
}
