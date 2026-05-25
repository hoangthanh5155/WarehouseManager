<?php

namespace App\Services\Warehouse;

use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchOrder;
use App\Models\DeliveryBatchSerial;
use App\Models\FulfillmentOrderSerial;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryOrderFulfillmentService
{
    public function __construct(
        protected ExportStockService $exportStockService,
        protected DeliveryBatchSerialService $serialService
    ) {
    }

    public function deliver(DeliveryBatchOrder $batchOrder, ?int $userId = null): array
    {
        return DB::transaction(function () use ($batchOrder, $userId) {
            $batchOrder->load('fulfillmentOrder.items', 'fulfillmentOrder.preparedSerials', 'deliveryBatch');

            if ($batchOrder->status !== WarehouseConstants::DELIVERY_ORDER_READY) {
                throw ValidationException::withMessages([
                    'delivery_batch_order_id' => 'Don trong chuyen chua san sang de giao.',
                ]);
            }

            if ($batchOrder->fulfillmentOrder->preparedSerials->isNotEmpty()) {
                return $this->deliverPreparedOrder($batchOrder, $userId);
            }

            $assignedSerials = DeliveryBatchSerial::query()
                ->where('delivery_batch_order_id', $batchOrder->id)
                ->where('status', WarehouseConstants::DELIVERY_SERIAL_ASSIGNED)
                ->orderBy('serial_number')
                ->lockForUpdate()
                ->get();

            $this->assertAssignedSerialsMatchOrder($batchOrder, $assignedSerials);

            $order = $batchOrder->fulfillmentOrder;
            $exportItems = $order->items->map(function ($item) use ($assignedSerials) {
                $serials = $assignedSerials
                    ->where('fulfillment_order_item_id', $item->id)
                    ->pluck('serial_number')
                    ->values()
                    ->all();

                return [
                    'product_catalog_id' => $item->product_catalog_id,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'serials' => $serials,
                ];
            })->values()->all();

            $exportResult = $this->exportStockService->export([
                'export_type' => WarehouseConstants::EXPORT_NORMAL,
                'customer_type' => $order->customer_type,
                'customer_id' => $order->customer_id,
                'buyer_name' => $order->buyer_name,
                'company_name' => $order->company_name,
                'address' => $order->address,
                'tax_code' => $order->tax_code,
                'note' => 'Fulfillment order ' . $order->order_code,
                'main_items' => $exportItems,
            ], $userId);

            $now = now();
            DeliveryBatchSerial::query()
                ->whereIn('id', $assignedSerials->pluck('id'))
                ->update([
                    'status' => WarehouseConstants::DELIVERY_SERIAL_DELIVERED,
                    'active_product_id' => null,
                    'delivered_at' => $now,
                    'updated_by' => $userId,
                ]);

            $order->update(['status' => WarehouseConstants::FULFILLMENT_DELIVERED]);
            $batchOrder->update([
                'status' => WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                'delivered_at' => $now,
            ]);

            $this->completeBatchIfDone($batchOrder->deliveryBatch);

            return [
                'fulfillment_order_id' => $order->id,
                'delivery_batch_order_id' => $batchOrder->id,
                'export_voucher_id' => $exportResult['export_voucher_id'],
                'main_voucher_id' => $exportResult['main_voucher_id'],
                'sub_voucher_ids' => $exportResult['sub_voucher_ids'],
                'print_url' => $exportResult['print_url'],
            ];
        });
    }

    private function deliverPreparedOrder(DeliveryBatchOrder $batchOrder, ?int $userId = null): array
    {
        $order = $batchOrder->fulfillmentOrder;

        $preparedSerials = FulfillmentOrderSerial::query()
            ->where('fulfillment_order_id', $order->id)
            ->where('status', WarehouseConstants::ORDER_SERIAL_PREPARED)
            ->orderBy('serial_number_snapshot')
            ->lockForUpdate()
            ->get();

        $this->assertPreparedSerialsMatchOrder($batchOrder, $preparedSerials);

        $exportItems = $order->items->map(function ($item) use ($preparedSerials) {
            $serials = $preparedSerials
                ->where('fulfillment_order_item_id', $item->id)
                ->pluck('serial_number_snapshot')
                ->values()
                ->all();

            return [
                'product_catalog_id' => $item->product_catalog_id,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'serials' => $serials,
            ];
        })->values()->all();

        $exportResult = $this->exportStockService->export([
            'export_type' => WarehouseConstants::EXPORT_NORMAL,
            'customer_type' => $order->customer_type,
            'customer_id' => $order->customer_id,
            'buyer_name' => $order->buyer_name,
            'company_name' => $order->company_name,
            'address' => $order->address,
            'tax_code' => $order->tax_code,
            'note' => 'Fulfillment order ' . $order->order_code,
            'main_items' => $exportItems,
        ], $userId);

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
            'delivered_by' => $userId,
            'delivered_at' => $now,
            'export_voucher_id' => $exportResult['export_voucher_id'],
        ]);

        $batchOrder->update([
            'status' => WarehouseConstants::DELIVERY_ORDER_DELIVERED,
            'delivered_at' => $now,
        ]);

        $this->completeBatchIfDone($batchOrder->deliveryBatch);

        return [
            'fulfillment_order_id' => $order->id,
            'delivery_batch_order_id' => $batchOrder->id,
            'export_voucher_id' => $exportResult['export_voucher_id'],
            'main_voucher_id' => $exportResult['main_voucher_id'],
            'sub_voucher_ids' => $exportResult['sub_voucher_ids'],
            'print_url' => $exportResult['print_url'],
        ];
    }

    public function fail(DeliveryBatchOrder $batchOrder, ?string $note = null, ?int $userId = null): void
    {
        DB::transaction(function () use ($batchOrder, $note, $userId) {
            $batchOrder->load('fulfillmentOrder');
            $now = now();
            $batchOrder->update([
                'status' => WarehouseConstants::DELIVERY_ORDER_FAILED,
                'failed_at' => $now,
                'note' => $note,
            ]);
            $batchOrder->fulfillmentOrder->update([
                'status' => WarehouseConstants::FULFILLMENT_FAILED,
                'failed_at' => $now,
                'failure_reason' => $note,
            ]);
        });
    }

    private function assertAssignedSerialsMatchOrder(DeliveryBatchOrder $batchOrder, $assignedSerials): void
    {
        foreach ($batchOrder->fulfillmentOrder->items as $item) {
            $count = $assignedSerials->where('fulfillment_order_item_id', $item->id)->count();
            if ($count !== (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'serials' => 'So serial da xac minh khong khop dong hang ' . $item->product_name_snapshot . '.',
                ]);
            }
        }
    }

    private function assertPreparedSerialsMatchOrder(DeliveryBatchOrder $batchOrder, $preparedSerials): void
    {
        foreach ($batchOrder->fulfillmentOrder->items as $item) {
            $count = $preparedSerials->where('fulfillment_order_item_id', $item->id)->count();
            if ($count !== (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'serials' => 'Số SN đã soạn không khớp ' . $item->product_name_snapshot . '.',
                ]);
            }
        }
    }

    private function completeBatchIfDone(DeliveryBatch $batch): void
    {
        $hasOpenOrders = $batch->batchOrders()
            ->whereNotIn('status', [
                WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                WarehouseConstants::DELIVERY_ORDER_FAILED,
                WarehouseConstants::DELIVERY_ORDER_CANCELLED,
            ])
            ->exists();

        if (!$hasOpenOrders) {
            $batch->update([
                'status' => WarehouseConstants::DELIVERY_BATCH_COMPLETED,
                'completed_at' => now(),
            ]);
        }
    }
}
