<?php

namespace App\Http\Controllers;

use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchOrder;
use App\Models\DeliveryBatchSerial;
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
            ->with(['items.productCatalog', 'batchOrders.deliveryBatch.serials.productCatalog', 'preparedSerials.productCatalog'])
            ->withCount('items')
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
            'serials.productCatalog',
            'serials.fulfillmentOrder',
        ]);

        $availableOrders = FulfillmentOrder::query()
            ->whereIn('status', [
                WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
                WarehouseConstants::FULFILLMENT_PENDING,
                WarehouseConstants::FULFILLMENT_PENDING_PREPARE,
            ])
            ->whereDoesntHave('batchOrders', fn ($query) => $query->whereNotIn('status', [
                WarehouseConstants::DELIVERY_ORDER_FAILED,
                WarehouseConstants::DELIVERY_ORDER_CANCELLED,
            ]))
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
                    ->with(['items', 'batchOrders'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!in_array($order->status, [
                    WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
                    WarehouseConstants::FULFILLMENT_IN_DELIVERY,
                ], true)) {
                    throw ValidationException::withMessages(['order' => 'Don chua san sang giao.']);
                }

                $batchOrder = DeliveryBatchOrder::query()
                    ->where('fulfillment_order_id', $order->id)
                    ->whereNotIn('status', [
                        WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                        WarehouseConstants::DELIVERY_ORDER_FAILED,
                        WarehouseConstants::DELIVERY_ORDER_CANCELLED,
                    ])
                    ->latest()
                    ->lockForUpdate()
                    ->first();

                if (!$batchOrder) {
                    throw ValidationException::withMessages(['delivery_batch_id' => 'Don chua nam trong chuyen giao.']);
                }

                $scannedSerials = collect(preg_split('/\R+/', (string) $validated['serials']))
                    ->map(fn ($serial) => trim($serial))
                    ->filter()
                    ->values();

                if ($scannedSerials->isEmpty() || $scannedSerials->count() !== $scannedSerials->unique()->count()) {
                    throw ValidationException::withMessages(['serials' => 'SN xac nhan khong hop le.']);
                }

                $requiredQuantity = (int) $order->items->sum('quantity');
                if ($scannedSerials->count() !== $requiredQuantity) {
                    throw ValidationException::withMessages(['serials' => 'So SN xac nhan chua du so luong don.']);
                }

                $batchSerials = DeliveryBatchSerial::query()
                    ->where('delivery_batch_id', $batchOrder->delivery_batch_id)
                    ->whereIn('serial_number', $scannedSerials)
                    ->orderBy('serial_number')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('serial_number');

                $missing = $scannedSerials->reject(fn ($serial) => $batchSerials->has($serial))->values();
                if ($missing->isNotEmpty()) {
                    throw ValidationException::withMessages(['serials' => 'SN khong thuoc chuyen giao: ' . $missing->implode(', ')]);
                }

                $closed = $batchSerials->filter(fn (DeliveryBatchSerial $serial) => in_array($serial->status, [
                    WarehouseConstants::DELIVERY_SERIAL_DELIVERED,
                    WarehouseConstants::DELIVERY_SERIAL_RELEASED,
                ], true));
                if ($closed->isNotEmpty()) {
                    throw ValidationException::withMessages(['serials' => 'SN da giao hoac khong con kha dung: ' . $closed->pluck('serial_number')->implode(', ')]);
                }

                $usedByOtherOrder = $batchSerials->filter(function (DeliveryBatchSerial $serial) use ($order) {
                    return $serial->fulfillment_order_id && (int) $serial->fulfillment_order_id !== (int) $order->id;
                });
                if ($usedByOtherOrder->isNotEmpty()) {
                    throw ValidationException::withMessages(['serials' => 'SN da gan cho don khac: ' . $usedByOtherOrder->pluck('serial_number')->implode(', ')]);
                }

                $requiredCatalogIds = $order->items->pluck('product_catalog_id')->unique();
                $wrongCatalogs = $batchSerials
                    ->reject(fn (DeliveryBatchSerial $serial) => $requiredCatalogIds->contains($serial->product_catalog_id))
                    ->pluck('serial_number')
                    ->values();
                if ($wrongCatalogs->isNotEmpty()) {
                    throw ValidationException::withMessages(['serials' => 'SN sai san pham: ' . $wrongCatalogs->implode(', ')]);
                }

                foreach ($order->items as $item) {
                    $count = $batchSerials->where('product_catalog_id', $item->product_catalog_id)->count();
                    if ($count !== (int) $item->quantity) {
                        throw ValidationException::withMessages(['serials' => 'So SN khong khop ' . $item->product_name_snapshot . '.']);
                    }
                }

                $exportItems = $order->items->map(function ($item) use ($batchSerials) {
                    return [
                        'product_catalog_id' => $item->product_catalog_id,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'serials' => $batchSerials
                            ->where('product_catalog_id', $item->product_catalog_id)
                            ->pluck('serial_number')
                            ->values()
                            ->all(),
                    ];
                })->values()->all();

                $result = $exportStockService->export([
                    'export_type' => WarehouseConstants::EXPORT_NORMAL,
                    'customer_type' => $order->customer_type,
                    'customer_id' => $order->customer_id,
                    'buyer_name' => $order->buyer_name ?: $order->order_code,
                    'company_name' => $order->company_name,
                    'address' => $order->address,
                    'tax_code' => $order->tax_code,
                    'note' => $validated['note'] ?? ('Fulfillment order ' . $order->order_code),
                    'main_items' => $exportItems,
                ], $request->user()?->id);

                $now = now();
                foreach ($order->items as $item) {
                    $itemSerials = $batchSerials->where('product_catalog_id', $item->product_catalog_id)->values();
                    foreach ($itemSerials as $batchSerial) {
                        FulfillmentOrderSerial::query()->create([
                            'fulfillment_order_id' => $order->id,
                            'fulfillment_order_item_id' => $item->id,
                            'product_id' => $batchSerial->product_id,
                            'active_product_id' => null,
                            'product_catalog_id' => $batchSerial->product_catalog_id,
                            'serial_number_snapshot' => $batchSerial->serial_number,
                            'status' => WarehouseConstants::ORDER_SERIAL_DELIVERED,
                            'prepared_by' => $request->user()?->id,
                            'prepared_at' => $now,
                            'delivered_at' => $now,
                        ]);

                        $batchSerial->update([
                            'delivery_batch_order_id' => $batchOrder->id,
                            'fulfillment_order_id' => $order->id,
                            'fulfillment_order_item_id' => $item->id,
                            'status' => WarehouseConstants::DELIVERY_SERIAL_DELIVERED,
                            'active_product_id' => null,
                            'assigned_at' => $batchSerial->assigned_at ?: $now,
                            'delivered_at' => $now,
                            'updated_by' => $request->user()?->id,
                        ]);
                    }
                }

                $order->update([
                    'status' => WarehouseConstants::FULFILLMENT_DELIVERED,
                    'delivered_by' => $request->user()?->id,
                    'delivered_at' => $now,
                    'export_voucher_id' => $result['export_voucher_id'],
                ]);

                $batchOrder->update([
                    'status' => WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                    'delivered_at' => $now,
                ]);
            });

            return redirect()->route('delivery.orders.index')->with('success', 'Da xac nhan giao hang.');
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    public function fail(Request $request, FulfillmentOrder $fulfillmentOrder)
    {
        $validated = $request->validate([
            'failure_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($fulfillmentOrder, $validated) {
            $order = FulfillmentOrder::query()
                ->whereKey($fulfillmentOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            $now = now();
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

        return redirect()->route('delivery.orders.index')->with('success', 'Da danh dau giao that bai.');
    }
}
