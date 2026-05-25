<?php

namespace App\Services\Warehouse;

use App\Models\FulfillmentOrder;
use App\Models\Product;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FulfillmentPreparationService
{
    public function __construct(
        protected DeliveryBatchService $batchService,
        protected DeliveryBatchSerialService $batchSerialService
    ) {
    }

    public function prepareNormal(array $payload, ?int $userId = null): array
    {
        $serials = $this->normalizeSerials($payload['serials'] ?? []);
        if ($serials->isEmpty()) {
            throw ValidationException::withMessages(['serials' => 'Vui long quet SN.']);
        }

        return DB::transaction(function () use ($payload, $serials, $userId) {
            $batch = $this->batchService->create([
                'note' => $payload['note'] ?? 'Hang xuat thuong cho giao',
            ], $userId);

            $reservations = $this->batchSerialService->reserveSerials($batch, $serials->all(), $userId);

            return [
                'delivery_batch' => $batch->refresh(),
                'reserved_serials_count' => $reservations->count(),
                'print_url' => route('delivery.batches.show', $batch),
            ];
        });
    }

    public function prepareSystemOrder(FulfillmentOrder $order, array $serials, ?int $userId = null): FulfillmentOrder
    {
        $serials = $this->normalizeSerials($serials);
        if ($serials->isEmpty()) {
            throw ValidationException::withMessages(['serials' => 'Vui long quet SN.']);
        }

        return DB::transaction(function () use ($order, $serials, $userId) {
            $order = FulfillmentOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($order->status, [
                WarehouseConstants::FULFILLMENT_PENDING,
                WarehouseConstants::FULFILLMENT_PENDING_PREPARE,
                WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
            ], true)) {
                throw ValidationException::withMessages(['order' => 'Don khong o trang thai cho dua vao chuyen.']);
            }

            $order->load('items.productCatalog');
            $requiredQuantity = (int) $order->items->sum('quantity');
            if ($serials->count() !== $requiredQuantity) {
                throw ValidationException::withMessages(['serials' => 'So SN khong khop so luong can giao.']);
            }

            $batch = $this->batchService->create([
                'note' => 'Chuyen giao cho don ' . $order->order_code,
            ], $userId);

            $this->batchService->addOrder($batch, $order);
            $reservations = $this->batchSerialService->reserveSerials($batch, $serials->all(), $userId);
            $remaining = $reservations->values()->groupBy('product_catalog_id')
                ->map(fn (Collection $items) => $items->values());

            foreach ($order->items as $item) {
                $catalogReservations = $remaining->get($item->product_catalog_id, collect());
                if ($catalogReservations->count() < (int) $item->quantity) {
                    throw ValidationException::withMessages(['serials' => 'Chua du SN cho ' . $item->product_name_snapshot . '.']);
                }

                $remaining->put($item->product_catalog_id, $catalogReservations->slice((int) $item->quantity)->values());
            }

            $extraSerials = $remaining
                ->flatMap(fn (Collection $items) => $items)
                ->pluck('serial_number')
                ->values();

            if ($extraSerials->isNotEmpty()) {
                throw ValidationException::withMessages(['serials' => 'SN sai san pham: ' . $extraSerials->implode(', ')]);
            }

            $order->update([
                'status' => WarehouseConstants::FULFILLMENT_IN_DELIVERY,
                'public_token' => $order->public_token ?: (string) Str::uuid(),
                'prepared_by' => $userId,
                'prepared_at' => now(),
            ]);

            return $order->refresh()->load('items.productCatalog', 'batchOrders.deliveryBatch');
        });
    }

    private function lockAvailableProducts(Collection $serials): Collection
    {
        $products = Product::query()
            ->with('productCatalog', 'location')
            ->whereIn('serial_number', $serials)
            ->orderBy('serial_number')
            ->lockForUpdate()
            ->get()
            ->keyBy('serial_number');

        $missing = $serials->reject(fn ($serial) => $products->has($serial))->values();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['serials' => 'SN khong co trong kho: ' . $missing->implode(', ')]);
        }

        $notInStock = $products->filter(fn (Product $product) => (int) $product->status !== WarehouseConstants::PRODUCT_STATUS_IN_STOCK);
        if ($notInStock->isNotEmpty()) {
            throw ValidationException::withMessages(['serials' => 'SN khong co trong kho: ' . $notInStock->pluck('serial_number')->implode(', ')]);
        }

        return $products;
    }

    private function normalizeSerials(array $serials): Collection
    {
        $serials = collect($serials)
            ->map(fn ($serial) => is_array($serial) ? ($serial['serial_number'] ?? $serial['serial'] ?? '') : $serial)
            ->map(fn ($serial) => trim((string) $serial))
            ->filter()
            ->values();

        if ($serials->count() !== $serials->unique()->count()) {
            throw ValidationException::withMessages(['serials' => 'SN da bi trung.']);
        }

        return $serials->unique()->sort()->values();
    }
}
