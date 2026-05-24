<?php

namespace App\Services\Warehouse;

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
}
