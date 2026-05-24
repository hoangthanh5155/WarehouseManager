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
    public function prepareNormal(array $payload, ?int $userId = null): FulfillmentOrder
    {
        $serials = $this->normalizeSerials($payload['serials'] ?? []);
        if ($serials->isEmpty()) {
            throw ValidationException::withMessages(['serials' => 'Vui lòng quét SN.']);
        }

        $buyerName = trim((string) ($payload['buyer_name'] ?? ''));
        if ($buyerName === '') {
            throw ValidationException::withMessages(['buyer_name' => 'Vui lòng nhập người mua.']);
        }

        return DB::transaction(function () use ($payload, $serials, $buyerName, $userId) {
            $products = $this->lockAvailableProducts($serials);
            $now = now();

            $order = FulfillmentOrder::query()->create([
                'order_code' => $payload['order_code'] ?? $this->generateOrderCode(),
                'public_token' => (string) Str::uuid(),
                'order_type' => WarehouseConstants::ORDER_TYPE_MANUAL,
                'customer_id' => $payload['customer_id'] ?? null,
                'customer_type' => $payload['customer_type'] ?? WarehouseConstants::CUSTOMER_RETAIL,
                'buyer_name' => $buyerName,
                'company_name' => $payload['company_name'] ?? null,
                'address' => $payload['address'] ?? null,
                'tax_code' => $payload['tax_code'] ?? null,
                'status' => WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
                'note' => $payload['note'] ?? null,
                'created_by' => $userId,
                'prepared_by' => $userId,
                'prepared_at' => $now,
            ]);

            $products->values()
                ->groupBy('product_catalog_id')
                ->each(function (Collection $catalogProducts) use ($order, $payload, $userId, $now) {
                    $catalog = $catalogProducts->first()->productCatalog;
                    $unitPrice = (float) (($payload['customer_type'] ?? WarehouseConstants::CUSTOMER_RETAIL) === WarehouseConstants::CUSTOMER_AGENCY
                        ? $catalog->agency_price
                        : $catalog->retail_price);

                    $item = $order->items()->create([
                        'product_catalog_id' => $catalog->id,
                        'product_name_snapshot' => $catalog->product_name,
                        'quantity' => $catalogProducts->count(),
                        'unit_price' => $unitPrice,
                        'total_amount' => $unitPrice * $catalogProducts->count(),
                    ]);

                    foreach ($catalogProducts as $product) {
                        $this->createPreparedSerial($order, $item->id, $product, $userId, $now);
                    }
                });

            return $order->load('items.productCatalog', 'preparedSerials.productCatalog');
        });
    }

    public function prepareSystemOrder(FulfillmentOrder $order, array $serials, ?int $userId = null): FulfillmentOrder
    {
        $serials = $this->normalizeSerials($serials);
        if ($serials->isEmpty()) {
            throw ValidationException::withMessages(['serials' => 'Vui lòng quét SN.']);
        }

        return DB::transaction(function () use ($order, $serials, $userId) {
            $order = FulfillmentOrder::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($order->status, [
                WarehouseConstants::FULFILLMENT_PENDING,
                WarehouseConstants::FULFILLMENT_PENDING_PREPARE,
            ], true)) {
                throw ValidationException::withMessages(['order' => 'Đơn không ở trạng thái chờ soạn.']);
            }

            $order->load('items.productCatalog');
            $requiredQuantity = (int) $order->items->sum('quantity');
            if ($serials->count() !== $requiredQuantity) {
                throw ValidationException::withMessages(['serials' => 'Số SN không khớp số lượng cần giao.']);
            }

            $products = $this->lockAvailableProducts($serials);
            $remaining = $products->values()->groupBy('product_catalog_id')->map(fn ($items) => $items->values());
            $now = now();

            foreach ($order->items as $item) {
                $catalogProducts = $remaining->get($item->product_catalog_id, collect());
                if ($catalogProducts->count() < (int) $item->quantity) {
                    throw ValidationException::withMessages(['serials' => 'Chưa đủ SN cho ' . $item->product_name_snapshot . '.']);
                }

                $catalogProducts->take((int) $item->quantity)->each(function (Product $product) use ($order, $item, $userId, $now) {
                    $this->createPreparedSerial($order, $item->id, $product, $userId, $now);
                });

                $remaining->put($item->product_catalog_id, $catalogProducts->slice((int) $item->quantity)->values());
            }

            $extraSerials = $remaining
                ->flatMap(fn (Collection $items) => $items)
                ->pluck('serial_number')
                ->values();

            if ($extraSerials->isNotEmpty()) {
                throw ValidationException::withMessages(['serials' => 'SN sai sản phẩm: ' . $extraSerials->implode(', ')]);
            }

            $order->update([
                'status' => WarehouseConstants::FULFILLMENT_READY_TO_DELIVER,
                'public_token' => $order->public_token ?: (string) Str::uuid(),
                'prepared_by' => $userId,
                'prepared_at' => $now,
            ]);

            return $order->refresh()->load('items.productCatalog', 'preparedSerials.productCatalog');
        });
    }

    private function createPreparedSerial(FulfillmentOrder $order, ?int $itemId, Product $product, ?int $userId, $now): void
    {
        $order->preparedSerials()->create([
            'fulfillment_order_item_id' => $itemId,
            'product_id' => $product->id,
            'active_product_id' => $product->id,
            'product_catalog_id' => $product->product_catalog_id,
            'serial_number_snapshot' => $product->serial_number,
            'status' => WarehouseConstants::ORDER_SERIAL_PREPARED,
            'prepared_by' => $userId,
            'prepared_at' => $now,
        ]);
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
            throw ValidationException::withMessages(['serials' => 'SN không có trong kho: ' . $missing->implode(', ')]);
        }

        $notInStock = $products->filter(fn (Product $product) => (int) $product->status !== WarehouseConstants::PRODUCT_STATUS_IN_STOCK);
        if ($notInStock->isNotEmpty()) {
            throw ValidationException::withMessages(['serials' => 'SN không có trong kho: ' . $notInStock->pluck('serial_number')->implode(', ')]);
        }

        $reserved = \App\Models\FulfillmentOrderSerial::query()
            ->whereIn('active_product_id', $products->pluck('id'))
            ->lockForUpdate()
            ->pluck('serial_number_snapshot');

        if ($reserved->isNotEmpty()) {
            throw ValidationException::withMessages(['serials' => 'SN đang được giữ cho đơn khác: ' . $reserved->implode(', ')]);
        }

        $batchReserved = \App\Models\DeliveryBatchSerial::query()
            ->whereIn('active_product_id', $products->pluck('id'))
            ->lockForUpdate()
            ->pluck('serial_number');

        if ($batchReserved->isNotEmpty()) {
            throw ValidationException::withMessages(['serials' => 'SN đang được giữ cho đơn khác: ' . $batchReserved->implode(', ')]);
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
            throw ValidationException::withMessages(['serials' => 'SN đã có trong đơn.']);
        }

        return $serials->unique()->sort()->values();
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'FO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (FulfillmentOrder::query()->where('order_code', $code)->exists());

        return $code;
    }
}
