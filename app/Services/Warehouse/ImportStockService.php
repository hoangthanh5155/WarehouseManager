<?php

namespace App\Services\Warehouse;

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
}
