<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class SerialTraceService
{
    public function findProduct(string $serial): ?Product
    {
        return Product::query()
            ->with(['productCatalog', 'supplier', 'location', 'importVoucher', 'exportVoucher'])
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

    public function trace(string $serial): array
    {
        return [
            'product' => $this->findProduct($serial),
            'movements' => $this->movements($serial),
        ];
    }
}
