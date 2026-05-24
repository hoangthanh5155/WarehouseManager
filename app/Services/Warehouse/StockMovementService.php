<?php

namespace App\Services\Warehouse;

use App\Models\ImportVoucher;
use App\Models\ExportVoucher;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Warehouse\WarehouseConstants;

class StockMovementService
{
    public function recordImport(Product $product, ImportVoucher $voucher, ?int $userId = null, mixed $occurredAt = null): StockMovement
    {
        return StockMovement::query()->create([
            'movement_type' => WarehouseConstants::MOVEMENT_IMPORT,
            'product_id' => $product->id,
            'serial_number' => $product->serial_number,
            'product_catalog_id' => $product->product_catalog_id,
            'supplier_id' => $product->supplier_id,
            'from_status' => null,
            'to_status' => WarehouseConstants::PRODUCT_STATUS_IN_STOCK,
            'from_location_id' => null,
            'to_location_id' => $product->location_id,
            'import_voucher_id' => $voucher->id,
            'user_id' => $userId,
            'quantity' => 1,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    public function recordExport(Product $product, ExportVoucher $voucher, ?int $userId = null, mixed $occurredAt = null): StockMovement
    {
        return StockMovement::query()->create([
            'movement_type' => WarehouseConstants::MOVEMENT_EXPORT,
            'product_id' => $product->id,
            'serial_number' => $product->serial_number,
            'product_catalog_id' => $product->product_catalog_id,
            'supplier_id' => $product->supplier_id,
            'from_status' => WarehouseConstants::PRODUCT_STATUS_IN_STOCK,
            'to_status' => WarehouseConstants::PRODUCT_STATUS_EXPORTED,
            'from_location_id' => $product->location_id,
            'to_location_id' => null,
            'export_voucher_id' => $voucher->id,
            'user_id' => $userId,
            'quantity' => 1,
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }
}
