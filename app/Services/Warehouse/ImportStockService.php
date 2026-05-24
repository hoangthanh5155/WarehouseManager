<?php

namespace App\Services\Warehouse;

use App\Models\ImportVoucher;
use App\Models\ImportVoucherItem;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Models\Supplier;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ImportStockService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
        protected PricingService $pricingService
    ) {
    }

    public function movementService(): StockMovementService
    {
        return $this->stockMovementService;
    }

    public function pricing(): PricingService
    {
        return $this->pricingService;
    }

    public function importScannedSerial(array $payload, ?int $userId = null): array
    {
        $serial = trim((string) ($payload['serial_number'] ?? ''));
        if ($serial === '') {
            throw ValidationException::withMessages([
                'serial_number' => 'Ma SN khong duoc de trong.',
            ]);
        }

        return $this->importSerials([$serial], $payload, $userId);
    }

    public function importGeneratedSerials(array $payload, ?int $userId = null): array
    {
        $quantity = max(1, (int) ($payload['quantity'] ?? 1));
        if ($quantity > 100) {
            throw ValidationException::withMessages([
                'quantity' => 'Chi ho tro tao toi da 100 ma mot lan.',
            ]);
        }

        return $this->importSerials($this->generateSerials($quantity), $payload, $userId);
    }

    public function importSerials(array $serials, array $payload, ?int $userId = null): array
    {
        $serials = collect($serials)
            ->map(fn ($serial) => trim((string) $serial))
            ->filter()
            ->unique()
            ->values();

        if ($serials->isEmpty()) {
            throw ValidationException::withMessages([
                'serial_number' => 'Danh sach SN khong duoc de trong.',
            ]);
        }

        $existingSerials = Product::query()
            ->whereIn('serial_number', $serials)
            ->pluck('serial_number')
            ->all();

        if ($existingSerials) {
            throw ValidationException::withMessages([
                'serial_number' => 'SN da ton tai: ' . implode(', ', $existingSerials),
            ]);
        }

        return DB::transaction(function () use ($serials, $payload, $userId) {
            $now = now();
            $supplier = $this->resolveSupplier($payload['supplier_id'] ?? null);
            $wholesalePrice = (float) ($payload['wholesale_price'] ?? 0);
            $catalog = $this->resolveProductCatalog($payload['product_catalog_id'] ?? null, $supplier, $wholesalePrice);
            $location = $this->resolveLocation($payload['location_id'] ?? null);
            $quantity = $serials->count();

            $voucher = ImportVoucher::query()->create([
                'import_code' => $this->generateImportCode(),
                'supplier_id' => $supplier->id,
                'product_catalog_id' => $catalog->id,
                'location_id' => $location->id,
                'wholesale_price' => $wholesalePrice,
                'total_quantity' => $quantity,
                'total_cost' => $wholesalePrice * $quantity,
                'user_id' => $userId,
                'imported_at' => $now,
            ]);

            $itemPayload = [
                'import_voucher_id' => $voucher->id,
                'product_catalog_id' => $catalog->id,
                'location_id' => $location->id,
                'quantity' => $quantity,
                'unit_cost' => $wholesalePrice,
                'total_cost' => $wholesalePrice * $quantity,
            ];

            if (Schema::hasColumn('import_voucher_items', 'product_name_snapshot')) {
                $itemPayload['product_name_snapshot'] = $catalog->product_name;
            }

            $item = ImportVoucherItem::query()->create($itemPayload);

            $products = $serials->map(function (string $serial) use ($catalog, $supplier, $location, $voucher, $item, $now, $userId) {
                $productPayload = [
                    'product_catalog_id' => $catalog->id,
                    'supplier_id' => $supplier->id,
                    'location_id' => $location->id,
                    'serial_number' => $serial,
                    'status' => WarehouseConstants::PRODUCT_STATUS_IN_STOCK,
                    'import_voucher_id' => $voucher->id,
                    'imported_at' => $now,
                ];

                if (Schema::hasColumn('products', 'import_voucher_item_id')) {
                    $productPayload['import_voucher_item_id'] = $item->id;
                }

                $product = Product::query()->create($productPayload);
                $this->stockMovementService->recordImport($product, $voucher, $userId, $now);

                return $product;
            });

            return $this->formatResult($voucher, $item, $products, $catalog);
        });
    }

    private function resolveSupplier(mixed $value): Supplier
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw ValidationException::withMessages(['supplier_id' => 'Vui long chon nha cung cap.']);
        }

        return is_numeric($value)
            ? Supplier::query()->findOrFail((int) $value)
            : Supplier::query()->firstOrCreate(['name' => $value]);
    }

    private function resolveProductCatalog(mixed $value, Supplier $supplier, float $wholesalePrice): ProductCatalog
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw ValidationException::withMessages(['product_catalog_id' => 'Vui long chon san pham.']);
        }

        $catalog = is_numeric($value)
            ? ProductCatalog::query()->findOrFail((int) $value)
            : ProductCatalog::query()
                ->where('supplier_id', $supplier->id)
                ->where('product_name', $value)
                ->first();

        if (!$catalog) {
            $catalog = ProductCatalog::query()->create([
                'supplier_id' => $supplier->id,
                'product_name' => $value,
                'model_prefix' => $this->generateModelPrefix($value),
                ...$this->pricingService->calculate($wholesalePrice, 0, 0),
            ]);
        } elseif ($wholesalePrice > 0) {
            $catalog->update($this->pricingService->calculate(
                $wholesalePrice,
                $catalog->agency_margin,
                $catalog->profit_margin
            ));
        }

        return $catalog;
    }

    private function resolveLocation(mixed $value): Location
    {
        $value = trim((string) $value);
        if ($value === '') {
            throw ValidationException::withMessages(['location_id' => 'Vui long chon vi tri ke.']);
        }

        return is_numeric($value)
            ? Location::query()->findOrFail((int) $value)
            : Location::query()->firstOrCreate(['shelf_name' => $value]);
    }

    private function generateSerials(int $quantity): array
    {
        $serials = [];
        while (count($serials) < $quantity) {
            $serial = 'SN' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
            if (!in_array($serial, $serials, true) && !Product::query()->where('serial_number', $serial)->exists()) {
                $serials[] = $serial;
            }
        }

        return $serials;
    }

    private function generateImportCode(): string
    {
        do {
            $code = 'PN-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while (ImportVoucher::query()->where('import_code', $code)->exists());

        return $code;
    }

    private function generateModelPrefix(string $productName): string
    {
        $base = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $productName) ?: 'PRD', 0, 8));

        do {
            $prefix = $base . '-' . random_int(1000, 9999);
        } while (ProductCatalog::query()->where('model_prefix', $prefix)->exists());

        return $prefix;
    }

    private function formatResult(ImportVoucher $voucher, ImportVoucherItem $item, Collection $products, ProductCatalog $catalog): array
    {
        return [
            'import_voucher_id' => $voucher->id,
            'import_code' => $voucher->import_code,
            'import_voucher_item_id' => $item->id,
            'quantity' => $products->count(),
            'products' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'serial_number' => $product->serial_number,
                'status' => $product->status,
                'product_catalog_id' => $product->product_catalog_id,
                'location_id' => $product->location_id,
            ])->values()->all(),
            'print_items' => $products->map(fn (Product $product) => [
                'sn' => $product->serial_number,
                'name' => $catalog->product_name,
            ])->values()->all(),
        ];
    }
}
