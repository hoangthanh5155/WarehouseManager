<?php

namespace App\Services\Warehouse;

use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchOrder;
use App\Models\FulfillmentOrder;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryBatchService
{
    public function create(array $payload = [], ?int $userId = null): DeliveryBatch
    {
        return DeliveryBatch::query()->create([
            'batch_code' => $payload['batch_code'] ?? $this->generateBatchCode(),
            'status' => WarehouseConstants::DELIVERY_BATCH_DRAFT,
            'note' => $payload['note'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public function addOrder(DeliveryBatch $batch, FulfillmentOrder $order): DeliveryBatchOrder
    {
        if (in_array($batch->status, [
            WarehouseConstants::DELIVERY_BATCH_COMPLETED,
            WarehouseConstants::DELIVERY_BATCH_CANCELLED,
        ], true)) {
            throw ValidationException::withMessages(['delivery_batch_id' => 'Chuyen giao da dong, khong the them don.']);
        }

        if ($order->status !== WarehouseConstants::FULFILLMENT_READY_TO_DELIVER) {
            throw ValidationException::withMessages(['fulfillment_order_id' => 'Đơn chưa sẵn sàng giao.']);
        }

        return DB::transaction(function () use ($batch, $order) {
            $batchOrder = DeliveryBatchOrder::query()->firstOrCreate(
                [
                    'delivery_batch_id' => $batch->id,
                    'fulfillment_order_id' => $order->id,
                ],
                ['status' => WarehouseConstants::DELIVERY_ORDER_READY]
            );

            if ($batch->status === WarehouseConstants::DELIVERY_BATCH_DRAFT) {
                $batch->update(['status' => WarehouseConstants::DELIVERY_BATCH_PICKING]);
            }

            if ($order->status === WarehouseConstants::FULFILLMENT_READY_TO_DELIVER) {
                $order->update(['status' => WarehouseConstants::FULFILLMENT_IN_DELIVERY]);
            }

            return $batchOrder->load('fulfillmentOrder.items');
        });
    }

    public function markReadyIfPicked(DeliveryBatch $batch): DeliveryBatch
    {
        $pendingOrders = $batch->batchOrders()
            ->whereNotIn('status', [
                WarehouseConstants::DELIVERY_ORDER_READY,
                WarehouseConstants::DELIVERY_ORDER_DELIVERED,
                WarehouseConstants::DELIVERY_ORDER_FAILED,
                WarehouseConstants::DELIVERY_ORDER_CANCELLED,
            ])
            ->exists();

        if (!$pendingOrders && $batch->status === WarehouseConstants::DELIVERY_BATCH_PICKING) {
            $batch->update(['status' => WarehouseConstants::DELIVERY_BATCH_READY]);
        }

        return $batch->refresh();
    }

    private function generateBatchCode(): string
    {
        do {
            $code = 'DB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (DeliveryBatch::query()->where('batch_code', $code)->exists());

        return $code;
    }
}
