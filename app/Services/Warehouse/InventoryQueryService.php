<?php

namespace App\Services\Warehouse;

use App\Models\Product;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InventoryQueryService
{
    public function countCurrentStock(): int
    {
        return Product::query()
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->count();
    }

    public function countStockByCatalog(int $productCatalogId): int
    {
        return Product::query()
            ->where('product_catalog_id', $productCatalogId)
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->count();
    }

    public function catalogsWithStockCounts()
    {
        return DB::table('product_catalogs')
            ->leftJoin('products', function ($join) {
                $join->on('products.product_catalog_id', '=', 'product_catalogs.id')
                    ->where('products.status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK);
            })
            ->leftJoin('suppliers', 'product_catalogs.supplier_id', '=', 'suppliers.id')
            ->select('product_catalogs.*', 'suppliers.name as supplier_name')
            ->selectRaw('COUNT(products.id) as stock_count')
            ->groupBy(
                'product_catalogs.id',
                'product_catalogs.supplier_id',
                'product_catalogs.product_name',
                'product_catalogs.model_prefix',
                'product_catalogs.wholesale_price',
                'product_catalogs.agency_margin',
                'product_catalogs.profit_margin',
                'product_catalogs.agency_price',
                'product_catalogs.retail_price',
                'product_catalogs.created_at',
                'product_catalogs.updated_at',
                'suppliers.name'
            );
    }

    public function inStockSerialsByCatalog(int $productCatalogId): Collection
    {
        return Product::query()
            ->where('product_catalog_id', $productCatalogId)
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->orderBy('serial_number')
            ->get();
    }

    public function findSerial(string $serial): ?Product
    {
        return Product::query()
            ->with(['productCatalog', 'supplier', 'location'])
            ->where('serial_number', trim($serial))
            ->first();
    }

    public function findInStockSerial(string $serial, ?int $productCatalogId = null): ?Product
    {
        return Product::query()
            ->where('serial_number', trim($serial))
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->when($productCatalogId, fn ($query) => $query->where('product_catalog_id', $productCatalogId))
            ->first();
    }

    public function serialIsAvailableForCatalog(string $serial, int $productCatalogId): bool
    {
        return (bool) $this->findInStockSerial($serial, $productCatalogId);
    }
}
