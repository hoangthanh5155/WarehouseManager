<?php

namespace App\Services\Warehouse;

use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\ExportVoucher;
use App\Models\ExportVoucherItem;
use App\Models\ExportVoucherItemSerial;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ExportStockService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected InventoryQueryService $inventoryQueryService
    ) {
    }

    public function movementService(): StockMovementService
    {
        return $this->stockMovementService;
    }

    public function inventory(): InventoryQueryService
    {
        return $this->inventoryQueryService;
    }

    public function export(array $payload, ?int $userId = null): array
    {
        $mainItems = $payload['main_items'] ?? $payload['items'] ?? [];
        if (!is_array($mainItems) || count($mainItems) === 0) {
            throw ValidationException::withMessages([
                'main_items' => 'Vui long chon it nhat mot dong hang can xuat.',
            ]);
        }

        $buyerName = trim((string) ($payload['buyer_name'] ?? ''));
        if ($buyerName === '') {
            throw ValidationException::withMessages([
                'buyer_name' => 'Vui long nhap ten nguoi mua.',
            ]);
        }

        return DB::transaction(function () use ($payload, $userId, $mainItems) {
            $now = now();
            $customerId = $this->resolveCustomerId($payload);
            $companyProfile = CompanyProfile::current();
            $mainExportCode = $this->generateExportCode();
            $usedSerials = collect();
            $lockedProducts = $this->lockProductsForExport($mainItems, $payload['sub_vouchers'] ?? []);

            $mainVoucher = $this->createVoucher(
                payload: $payload,
                items: $mainItems,
                exportCode: $mainExportCode,
                parentId: null,
                customerId: $customerId,
                companyProfile: $companyProfile,
                exportedAt: $now,
                userId: $userId,
                usedSerials: $usedSerials,
                lockedProducts: $lockedProducts
            );

            $subVoucherIds = [];
            foreach (($payload['sub_vouchers'] ?? []) as $index => $subVoucherPayload) {
                $subItems = $subVoucherPayload['items'] ?? [];
                if (!is_array($subItems) || count($subItems) === 0) {
                    continue;
                }

                $subPayload = array_merge($payload, [
                    'note' => $subVoucherPayload['note'] ?? 'Don mo rong cua ' . $mainExportCode,
                ]);

                $subVoucher = $this->createVoucher(
                    payload: $subPayload,
                    items: $subItems,
                    exportCode: $mainExportCode . '-MR' . ($index + 1),
                    parentId: $mainVoucher->id,
                    customerId: $customerId,
                    companyProfile: $companyProfile,
                    exportedAt: $now,
                    userId: $userId,
                    usedSerials: $usedSerials,
                    lockedProducts: $lockedProducts
                );

                $subVoucherIds[] = $subVoucher->id;
            }

            return [
                'export_voucher_id' => $mainVoucher->id,
                'main_voucher_id' => $mainVoucher->id,
                'sub_voucher_ids' => $subVoucherIds,
                'print_url' => route('export.print', $mainVoucher->id),
            ];
        });
    }

    private function createVoucher(
        array $payload,
        array $items,
        string $exportCode,
        ?int $parentId,
        ?int $customerId,
        ?CompanyProfile $companyProfile,
        mixed $exportedAt,
        ?int $userId,
        Collection $usedSerials,
        Collection $lockedProducts
    ): ExportVoucher {
        $preparedItems = $this->prepareItems($items, $usedSerials, $lockedProducts);
        $totals = $this->calculateTotals($preparedItems);

        $voucher = ExportVoucher::query()->create([
            'parent_id' => $parentId,
            'export_code' => $exportCode,
            'export_type' => $payload['export_type'] ?? WarehouseConstants::EXPORT_NORMAL,
            'customer_type' => $payload['customer_type'] ?? WarehouseConstants::CUSTOMER_RETAIL,
            'seller_name' => $companyProfile?->company_name ?: CompanyProfile::fallbackName(),
            'seller_tax_code' => $companyProfile?->tax_code ?: '',
            'seller_address' => $companyProfile?->address ?: '',
            'seller_phone' => $companyProfile?->hotline ?: '',
            'seller_bank_account' => $companyProfile?->bank_account ?: '',
            'seller_bank_name' => $companyProfile?->bank_name ?: '',
            'customer_id' => $customerId,
            'buyer_name' => $payload['buyer_name'] ?? null,
            'company_name' => $payload['company_name'] ?? null,
            'address' => $payload['address'] ?? null,
            'tax_code' => $payload['tax_code'] ?? null,
            'items' => $this->snapshotItems($preparedItems),
            'total_cost' => $totals['total_cost'],
            'total_amount' => $totals['total_amount'],
            'note' => $payload['note'] ?? null,
            'exported_at' => $exportedAt,
        ]);

        foreach ($preparedItems as $preparedItem) {
            $voucherItemPayload = [
                'export_voucher_id' => $voucher->id,
                'product_catalog_id' => $preparedItem['catalog']->id,
                'quantity' => $preparedItem['quantity'],
                'unit_cost' => $preparedItem['unit_cost'],
                'unit_price' => $preparedItem['unit_price'],
                'total_cost' => $preparedItem['unit_cost'] * $preparedItem['quantity'],
                'total_amount' => $preparedItem['unit_price'] * $preparedItem['quantity'],
            ];

            if (Schema::hasColumn('export_voucher_items', 'product_name_snapshot')) {
                $voucherItemPayload['product_name_snapshot'] = $preparedItem['catalog']->product_name;
            }

            $voucherItem = ExportVoucherItem::query()->create($voucherItemPayload);

            foreach ($preparedItem['products'] as $product) {
                ExportVoucherItemSerial::query()->create([
                    'export_voucher_item_id' => $voucherItem->id,
                    'product_id' => $product->id,
                    'serial_number' => $product->serial_number,
                ]);

                $updatePayload = [
                    'status' => WarehouseConstants::PRODUCT_STATUS_EXPORTED,
                    'export_voucher_id' => $voucher->id,
                    'exported_at' => $exportedAt,
                    'updated_at' => $exportedAt,
                ];

                if (Schema::hasColumn('products', 'export_voucher_item_id')) {
                    $updatePayload['export_voucher_item_id'] = $voucherItem->id;
                }

                $product->update($updatePayload);
                $this->stockMovementService->recordExport($product->fresh(), $voucher, $userId, $exportedAt);
            }
        }

        return $voucher;
    }

    private function prepareItems(array $items, Collection $usedSerials, Collection $lockedProducts): Collection
    {
        return collect($items)->values()->map(function (array $item, int $index) use ($usedSerials, $lockedProducts) {
            $catalogId = (int) ($item['product_catalog_id'] ?? $item['product_id'] ?? 0);
            if ($catalogId <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.product_catalog_id" => 'Dong hang thieu product_catalog_id.',
                ]);
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.quantity" => 'So luong phai lon hon 0.',
                ]);
            }

            $unitPrice = (float) ($item['unit_price'] ?? $item['price'] ?? 0);
            if ($unitPrice < 0) {
                throw ValidationException::withMessages([
                    "items.$index.unit_price" => 'Don gia khong hop le.',
                ]);
            }

            $serials = collect($item['serials'] ?? [])
                ->map(fn ($serial) => trim((string) $serial))
                ->filter()
                ->values();

            if ($serials->count() !== $quantity) {
                throw ValidationException::withMessages([
                    "items.$index.serials" => 'So luong serial phai bang so luong xuat.',
                ]);
            }

            $duplicatesInLine = $serials->duplicates()->unique()->values();
            if ($duplicatesInLine->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.$index.serials" => 'Serial bi trung trong dong hang: ' . $duplicatesInLine->implode(', '),
                ]);
            }

            $duplicatesInVoucher = $serials->intersect($usedSerials)->values();
            if ($duplicatesInVoucher->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.$index.serials" => 'Serial bi trung trong don xuat: ' . $duplicatesInVoucher->implode(', '),
                ]);
            }
            $serials->each(fn ($serial) => $usedSerials->push($serial));

            $catalog = ProductCatalog::query()->find($catalogId);
            if (!$catalog) {
                throw ValidationException::withMessages([
                    "items.$index.product_catalog_id" => 'Khong tim thay san pham trong danh muc.',
                ]);
            }

            $products = $serials
                ->mapWithKeys(fn ($serial) => [$serial => $lockedProducts->get($serial)])
                ->filter();

            $missing = $serials->reject(fn ($serial) => $products->has($serial))->values();
            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.$index.serials" => 'Serial khong ton tai: ' . $missing->implode(', '),
                ]);
            }

            $notInStock = $products
                ->filter(fn (Product $product) => (int) $product->status !== WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
                ->pluck('serial_number')
                ->values();
            if ($notInStock->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.$index.serials" => 'Serial da xuat hoac khong con trong kho: ' . $notInStock->implode(', '),
                ]);
            }

            $wrongCatalog = $products
                ->filter(fn (Product $product) => (int) $product->product_catalog_id !== $catalogId)
                ->pluck('serial_number')
                ->values();
            if ($wrongCatalog->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.$index.serials" => 'Serial khong thuoc dung san pham: ' . $wrongCatalog->implode(', '),
                ]);
            }

            return [
                'catalog' => $catalog,
                'quantity' => $quantity,
                'unit_cost' => (float) $catalog->wholesale_price,
                'unit_price' => $unitPrice,
                'serials' => $serials,
                'products' => $serials->map(fn ($serial) => $products->get($serial))->values(),
            ];
        });
    }

    private function lockProductsForExport(array $mainItems, mixed $subVouchers): Collection
    {
        $allItems = collect($mainItems);

        collect(is_array($subVouchers) ? $subVouchers : [])
            ->each(function ($subVoucher) use ($allItems) {
                $items = is_array($subVoucher) ? ($subVoucher['items'] ?? []) : [];
                if (is_array($items)) {
                    $allItems->push(...$items);
                }
            });

        $serials = $allItems
            ->flatMap(fn ($item) => is_array($item) ? ($item['serials'] ?? []) : [])
            ->map(fn ($serial) => trim((string) $serial))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($serials->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('serial_number', $serials)
            ->orderBy('serial_number')
            ->lockForUpdate()
            ->get()
            ->keyBy('serial_number');
    }

    private function calculateTotals(Collection $items): array
    {
        return [
            'total_cost' => $items->sum(fn ($item) => $item['unit_cost'] * $item['quantity']),
            'total_amount' => $items->sum(fn ($item) => $item['unit_price'] * $item['quantity']),
        ];
    }

    private function snapshotItems(Collection $items): array
    {
        return $items->map(fn ($item) => [
            'product_id' => $item['catalog']->id,
            'product_catalog_id' => $item['catalog']->id,
            'product_name' => $item['catalog']->product_name,
            'quantity' => $item['quantity'],
            'price' => $item['unit_price'],
            'unit_price' => $item['unit_price'],
            'serials' => $item['serials']->values()->all(),
        ])->values()->all();
    }

    private function resolveCustomerId(array $payload): ?int
    {
        $customerId = $payload['customer_id'] ?? null;
        if ($customerId) {
            return (int) $customerId;
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

    private function generateExportCode(): string
    {
        do {
            $code = 'PX-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (ExportVoucher::query()->where('export_code', $code)->exists());

        return $code;
    }
}
