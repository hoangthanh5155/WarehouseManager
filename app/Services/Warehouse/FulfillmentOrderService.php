<?php

namespace App\Services\Warehouse;

use App\Models\Customer;
use App\Models\FulfillmentOrder;
use App\Models\ProductCatalog;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FulfillmentOrderService
{
    public function create(array $payload, ?int $userId = null): FulfillmentOrder
    {
        $items = $payload['items'] ?? [];
        $buyerName = trim((string) ($payload['buyer_name'] ?? ''));

        if ($buyerName === '') {
            throw ValidationException::withMessages(['buyer_name' => 'Vui long nhap ten nguoi mua.']);
        }

        if (!is_array($items) || count($items) === 0) {
            throw ValidationException::withMessages(['items' => 'Don giao phai co it nhat mot dong hang.']);
        }

        return DB::transaction(function () use ($payload, $items, $buyerName, $userId) {
            $customerId = $this->resolveCustomerId($payload);

            $order = FulfillmentOrder::query()->create([
                'order_code' => $payload['order_code'] ?? $this->generateOrderCode(),
                'order_type' => $payload['order_type'] ?? WarehouseConstants::ORDER_TYPE_MANUAL,
                'customer_id' => $customerId,
                'store_id' => $payload['store_id'] ?? null,
                'customer_type' => $payload['customer_type'] ?? WarehouseConstants::CUSTOMER_RETAIL,
                'buyer_name' => $buyerName,
                'company_name' => $payload['company_name'] ?? null,
                'address' => $payload['address'] ?? null,
                'tax_code' => $payload['tax_code'] ?? null,
                'status' => WarehouseConstants::FULFILLMENT_PENDING,
                'note' => $payload['note'] ?? null,
                'created_by' => $userId,
            ]);

            foreach (array_values($items) as $index => $item) {
                $catalogId = (int) ($item['product_catalog_id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);
                $unitPrice = (float) ($item['unit_price'] ?? 0);

                if ($catalogId <= 0 || $quantity <= 0 || $unitPrice < 0) {
                    throw ValidationException::withMessages([
                        "items.$index" => 'Dong hang khong hop le.',
                    ]);
                }

                $catalog = ProductCatalog::query()->find($catalogId);
                if (!$catalog) {
                    throw ValidationException::withMessages([
                        "items.$index.product_catalog_id" => 'Khong tim thay san pham.',
                    ]);
                }

                $order->items()->create([
                    'product_catalog_id' => $catalog->id,
                    'product_name_snapshot' => $catalog->product_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_amount' => $quantity * $unitPrice,
                ]);
            }

            return $order->load('items.productCatalog');
        });
    }

    private function resolveCustomerId(array $payload): ?int
    {
        if (!empty($payload['customer_id'])) {
            return (int) $payload['customer_id'];
        }

        $buyerName = trim((string) ($payload['buyer_name'] ?? ''));
        if ($buyerName === '') {
            return null;
        }

        return Customer::query()->create([
            'name' => $buyerName,
            'company_name' => $payload['company_name'] ?? null,
            'address' => $payload['address'] ?? null,
            'tax_code' => $payload['tax_code'] ?? null,
            'type' => $payload['customer_type'] ?? WarehouseConstants::CUSTOMER_RETAIL,
        ])->id;
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'FO-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (FulfillmentOrder::query()->where('order_code', $code)->exists());

        return $code;
    }
}
