<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Collection;

class SerialTraceService
{
    public function findProduct(string $serial): ?Product
    {
        return Product::query()
            ->with([
                'productCatalog',
                'supplier',
                'location',
                'importVoucher.supplier',
                'importVoucherItem.productCatalog',
                'importVoucherItem.location',
                'exportVoucher',
                'exportVoucherItem.productCatalog',
                'exportVoucherItem.serials',
            ])
            ->where('serial_number', trim($serial))
            ->first();
    }

    public function movements(string $serial): Collection
    {
        return StockMovement::query()
            ->with(['fromLocation', 'toLocation', 'importVoucher', 'exportVoucher', 'user', 'productCatalog'])
            ->where('serial_number', trim($serial))
            ->orderBy('occurred_at')
            ->get();
    }

    public function trace(string $serial, bool $canViewCost = false): array
    {
        $product = $this->findProduct($serial);

        return [
            'product' => $product,
            'movements' => $this->movements($serial),
            'importVoucher' => $product?->importVoucher,
            'importVoucherItem' => $product?->importVoucherItem,
            'exportVoucher' => $product?->exportVoucher,
            'exportVoucherItem' => $product?->exportVoucherItem,
            'statusText' => $this->statusText($product?->status),
            'canViewCost' => $canViewCost,
        ];
    }

    public function statusText(mixed $status): string
    {
        return match ((int) $status) {
            WarehouseConstants::PRODUCT_STATUS_IN_STOCK => 'Con trong kho',
            WarehouseConstants::PRODUCT_STATUS_EXPORTED => 'Da xuat kho',
            default => 'Khong xac dinh',
        };
    }
}
