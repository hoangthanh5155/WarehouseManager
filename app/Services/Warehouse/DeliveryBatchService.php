<?php

namespace App\Services\Warehouse;

use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchOrder;
use App\Models\DeliveryBatchSerial;
use App\Models\DeliveryVehicle;
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
            'delivery_user_id' => $payload['delivery_user_id'] ?? null,
            'driver_user_id' => $payload['delivery_user_id'] ?? null,
            'vehicle_id' => $payload['vehicle_id'] ?? null,
            'vehicle_snapshot' => $this->vehicleSnapshot($payload['vehicle_id'] ?? null),
            'delivery_note' => $payload['delivery_note'] ?? null,
            'note' => $payload['note'] ?? null,
            'created_by' => $userId,
        ]);
    }

    public function update(DeliveryBatch $batch, array $payload): DeliveryBatch
    {
        if (in_array($batch->status, [
            WarehouseConstants::DELIVERY_BATCH_COMPLETED,
            WarehouseConstants::DELIVERY_BATCH_CANCELLED,
        ], true)) {
            throw ValidationException::withMessages(['delivery_batch_id' => 'Chuyen giao da dong, khong the sua.']);
        }

        $batch->update([
            'delivery_user_id' => $payload['delivery_user_id'] ?? null,
            'driver_user_id' => $payload['delivery_user_id'] ?? null,
            'vehicle_id' => $payload['vehicle_id'] ?? null,
            'vehicle_snapshot' => $this->vehicleSnapshot($payload['vehicle_id'] ?? null),
            'delivery_note' => $payload['delivery_note'] ?? null,
            'note' => $payload['note'] ?? $batch->note,
        ]);

        return $batch->refresh();
    }

    public function cancel(DeliveryBatch $batch, ?int $userId = null): void
    {
        DB::transaction(function () use ($batch, $userId) {
            $batch = DeliveryBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();

            if ($batch->status === WarehouseConstants::DELIVERY_BATCH_COMPLETED) {
                throw ValidationException::withMessages(['delivery_batch_id' => 'Khong the huy chuyen da hoan tat.']);
            }

            $hasDeliveredOrder = $batch->batchOrders()
                ->where('status', WarehouseConstants::DELIVERY_ORDER_DELIVERED)
                ->exists();
            if ($hasDeliveredOrder) {
                throw ValidationException::withMessages(['delivery_batch_id' => 'Khong the huy chuyen da co don giao thanh cong.']);
            }

            $orderIds = $batch->batchOrders()->pluck('fulfillment_order_id');
            FulfillmentOrder::query()
                ->whereIn('id', $orderIds)
                ->whereNotIn('status', [
                    WarehouseConstants::FULFILLMENT_DELIVERED,
                    WarehouseConstants::FULFILLMENT_FAILED,
                    WarehouseConstants::FULFILLMENT_CANCELLED,
                ])
                ->update(['status' => WarehouseConstants::FULFILLMENT_READY_TO_DELIVER]);

            DeliveryBatchSerial::query()
                ->where('delivery_batch_id', $batch->id)
                ->whereIn('status', [
                    WarehouseConstants::DELIVERY_SERIAL_RESERVED,
                    WarehouseConstants::DELIVERY_SERIAL_ASSIGNED,
                ])
                ->lockForUpdate()
                ->update([
                    'status' => WarehouseConstants::DELIVERY_SERIAL_RELEASED,
                    'active_product_id' => null,
                    'released_at' => now(),
                    'updated_by' => $userId,
                ]);

            $batch->batchOrders()->delete();
            $batch->update([
                'status' => WarehouseConstants::DELIVERY_BATCH_CANCELLED,
                'completed_at' => null,
            ]);
        });
    }

    public function addOrder(DeliveryBatch $batch, FulfillmentOrder $order): DeliveryBatchOrder
    {
        if (in_array($batch->status, [
            WarehouseConstants::DELIVERY_BATCH_COMPLETED,
            WarehouseConstants::DELIVERY_BATCH_CANCELLED,
        ], true)) {
            throw ValidationException::withMessages(['delivery_batch_id' => 'Chuyen giao da dong, khong the them don.']);
        }

        if (!in_array($order->status, [
            WarehouseConstants::FULFILLMENT_PENDING,
            WarehouseConstants::FULFILLMENT_PENDING_PREPARE,
            WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
            WarehouseConstants::FULFILLMENT_IN_DELIVERY,
        ], true)) {
            throw ValidationException::withMessages(['fulfillment_order_id' => 'Don chua san sang giao.']);
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

            if (in_array($order->status, [
                WarehouseConstants::FULFILLMENT_PENDING,
                WarehouseConstants::FULFILLMENT_PENDING_PREPARE,
                WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
            ], true)) {
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

    public function markReady(DeliveryBatch $batch): DeliveryBatch
    {
        if (!in_array($batch->status, [
            WarehouseConstants::DELIVERY_BATCH_DRAFT,
            WarehouseConstants::DELIVERY_BATCH_PICKING,
            WarehouseConstants::DELIVERY_BATCH_READY,
        ], true)) {
            throw ValidationException::withMessages(['delivery_batch_id' => 'Chi chuyen dang soan moi co the danh dau san sang.']);
        }

        $batch->update(['status' => WarehouseConstants::DELIVERY_BATCH_READY]);

        return $batch->refresh();
    }

    public function removeOrder(DeliveryBatchOrder $batchOrder): void
    {
        DB::transaction(function () use ($batchOrder) {
            $batchOrder = DeliveryBatchOrder::query()
                ->with('fulfillmentOrder')
                ->whereKey($batchOrder->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($batchOrder->status === WarehouseConstants::DELIVERY_ORDER_DELIVERED) {
                throw ValidationException::withMessages(['delivery_batch_order_id' => 'Khong the huy don da giao thanh cong.']);
            }

            $batchOrder->fulfillmentOrder?->update(['status' => WarehouseConstants::FULFILLMENT_READY_TO_DELIVER]);
            $batchOrder->delete();
        });
    }

    private function generateBatchCode(): string
    {
        do {
            $code = 'DB-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (DeliveryBatch::query()->where('batch_code', $code)->exists());

        return $code;
    }

    private function vehicleSnapshot(mixed $vehicleId): ?array
    {
        if (!$vehicleId) {
            return null;
        }

        $vehicle = DeliveryVehicle::query()->find($vehicleId);
        if (!$vehicle) {
            return null;
        }

        return [
            'id' => $vehicle->id,
            'vehicle_type' => $vehicle->vehicle_type,
            'plate_number' => $vehicle->plate_number,
            'load_capacity' => $vehicle->load_capacity,
        ];
    }
}
