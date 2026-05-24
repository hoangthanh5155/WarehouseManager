<?php

namespace App\Services\Warehouse;

use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchOrder;
use App\Models\DeliveryBatchSerial;
use App\Models\FulfillmentOrderSerial;
use App\Models\Product;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeliveryBatchSerialService
{
    public function reserveSerials(DeliveryBatch $batch, array $serials, ?int $userId = null): Collection
    {
        $serials = $this->normalizeSerials($serials);
        if ($serials->isEmpty()) {
            throw ValidationException::withMessages(['serials' => 'Danh sach serial khong duoc de trong.']);
        }

        return DB::transaction(function () use ($batch, $serials, $userId) {
            $products = $this->lockProducts($serials);
            $this->assertProductsCanBeReserved($serials, $products);

            $activeReservations = DeliveryBatchSerial::query()
                ->whereIn('active_product_id', $products->pluck('id'))
                ->lockForUpdate()
                ->get();

            if ($activeReservations->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serials' => 'Serial dang duoc giu o chuyen khac: ' . $activeReservations->pluck('serial_number')->implode(', '),
                ]);
            }

            $activeOrderReservations = FulfillmentOrderSerial::query()
                ->whereIn('active_product_id', $products->pluck('id'))
                ->lockForUpdate()
                ->get();

            if ($activeOrderReservations->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serials' => 'Serial dang duoc giu cho don khac: ' . $activeOrderReservations->pluck('serial_number_snapshot')->implode(', '),
                ]);
            }

            $now = now();
            $reservations = $serials->map(function (string $serial) use ($batch, $products, $now, $userId) {
                $product = $products->get($serial);

                return DeliveryBatchSerial::query()->create([
                    'delivery_batch_id' => $batch->id,
                    'product_id' => $product->id,
                    'active_product_id' => $product->id,
                    'product_catalog_id' => $product->product_catalog_id,
                    'serial_number' => $product->serial_number,
                    'status' => WarehouseConstants::DELIVERY_SERIAL_RESERVED,
                    'reserved_at' => $now,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
            });

            if ($batch->status === WarehouseConstants::DELIVERY_BATCH_DRAFT) {
                $batch->update(['status' => WarehouseConstants::DELIVERY_BATCH_PICKING]);
            }

            return $reservations;
        });
    }

    public function assignSerialsToOrder(DeliveryBatchOrder $batchOrder, array $serials, ?int $userId = null): Collection
    {
        $serials = $this->normalizeSerials($serials);
        if ($serials->isEmpty()) {
            throw ValidationException::withMessages(['serials' => 'Danh sach serial xac minh khong duoc de trong.']);
        }

        return DB::transaction(function () use ($batchOrder, $serials, $userId) {
            $batchOrder->load('fulfillmentOrder.items');
            $requiredQuantity = (int) $batchOrder->fulfillmentOrder->items->sum('quantity');
            if ($serials->count() !== $requiredQuantity) {
                throw ValidationException::withMessages([
                    'serials' => 'So luong serial xac minh phai bang tong so luong cua don.',
                ]);
            }

            $reservations = DeliveryBatchSerial::query()
                ->where('delivery_batch_id', $batchOrder->delivery_batch_id)
                ->whereIn('serial_number', $serials)
                ->lockForUpdate()
                ->get()
                ->keyBy('serial_number');

            $missing = $serials->reject(fn ($serial) => $reservations->has($serial))->values();
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serials' => 'Serial chua duoc giu trong chuyen giao: ' . $missing->implode(', '),
                ]);
            }

            $assignedElsewhere = $reservations->filter(function (DeliveryBatchSerial $reservation) use ($batchOrder) {
                return $reservation->status === WarehouseConstants::DELIVERY_SERIAL_ASSIGNED
                    && (int) $reservation->delivery_batch_order_id !== (int) $batchOrder->id;
            });

            if ($assignedElsewhere->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serials' => 'Serial da gan cho don khac: ' . $assignedElsewhere->pluck('serial_number')->implode(', '),
                ]);
            }

            $closed = $reservations->filter(fn (DeliveryBatchSerial $reservation) => in_array($reservation->status, [
                WarehouseConstants::DELIVERY_SERIAL_DELIVERED,
                WarehouseConstants::DELIVERY_SERIAL_RELEASED,
            ], true));

            if ($closed->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serials' => 'Serial khong con kha dung: ' . $closed->pluck('serial_number')->implode(', '),
                ]);
            }

            $assigned = collect();
            $remaining = $reservations->values()->groupBy('product_catalog_id')
                ->map(fn (Collection $catalogReservations) => $catalogReservations->values());
            $requiredCatalogIds = $batchOrder->fulfillmentOrder->items->pluck('product_catalog_id')->unique();
            $wrongCatalogs = $reservations
                ->reject(fn (DeliveryBatchSerial $reservation) => $requiredCatalogIds->contains($reservation->product_catalog_id))
                ->pluck('serial_number')
                ->values();

            if ($wrongCatalogs->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'serials' => 'Serial khong thuoc san pham cua don: ' . $wrongCatalogs->implode(', '),
                ]);
            }

            foreach ($batchOrder->fulfillmentOrder->items as $item) {
                $catalogReservations = $remaining->get($item->product_catalog_id, collect())->values();
                if ($catalogReservations->count() < $item->quantity) {
                    throw ValidationException::withMessages([
                        'serials' => 'Chua du serial cho san pham ' . $item->product_name_snapshot . '.',
                    ]);
                }

                $catalogReservations->take($item->quantity)->each(function (DeliveryBatchSerial $reservation) use ($batchOrder, $item, $userId, $assigned) {
                    $reservation->update([
                        'delivery_batch_order_id' => $batchOrder->id,
                        'fulfillment_order_id' => $batchOrder->fulfillment_order_id,
                        'fulfillment_order_item_id' => $item->id,
                        'status' => WarehouseConstants::DELIVERY_SERIAL_ASSIGNED,
                        'assigned_at' => now(),
                        'updated_by' => $userId,
                    ]);

                    $assigned->push($reservation->refresh());
                });

                $remaining->put($item->product_catalog_id, $catalogReservations->slice($item->quantity)->values());
            }

            $batchOrder->update(['status' => WarehouseConstants::DELIVERY_ORDER_READY]);
            $batchOrder->fulfillmentOrder->update(['status' => WarehouseConstants::FULFILLMENT_IN_DELIVERY]);

            return $assigned;
        });
    }

    public function releaseSerials(Collection $reservations, ?int $userId = null): void
    {
        DB::transaction(function () use ($reservations, $userId) {
            DeliveryBatchSerial::query()
                ->whereIn('id', $reservations->pluck('id'))
                ->whereIn('status', [
                    WarehouseConstants::DELIVERY_SERIAL_RESERVED,
                    WarehouseConstants::DELIVERY_SERIAL_ASSIGNED,
                ])
                ->update([
                    'status' => WarehouseConstants::DELIVERY_SERIAL_RELEASED,
                    'active_product_id' => null,
                    'released_at' => now(),
                    'updated_by' => $userId,
                ]);
        });
    }

    private function normalizeSerials(array $serials): Collection
    {
        return collect($serials)
            ->map(fn ($serial) => trim((string) $serial))
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function lockProducts(Collection $serials): Collection
    {
        return Product::query()
            ->whereIn('serial_number', $serials)
            ->orderBy('serial_number')
            ->lockForUpdate()
            ->get()
            ->keyBy('serial_number');
    }

    private function assertProductsCanBeReserved(Collection $serials, Collection $products): void
    {
        $missing = $serials->reject(fn ($serial) => $products->has($serial))->values();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages([
                'serials' => 'Serial khong ton tai: ' . $missing->implode(', '),
            ]);
        }

        $notInStock = $products
            ->filter(fn (Product $product) => (int) $product->status !== WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->pluck('serial_number')
            ->values();

        if ($notInStock->isNotEmpty()) {
            throw ValidationException::withMessages([
                'serials' => 'Serial da xuat hoac khong con trong kho: ' . $notInStock->implode(', '),
            ]);
        }
    }
}
