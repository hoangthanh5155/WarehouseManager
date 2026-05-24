<?php

namespace App\Support\Warehouse;

final class WarehouseConstants
{
    public const PRODUCT_STATUS_IN_STOCK = 1;
    public const PRODUCT_STATUS_EXPORTED = 2;

    public const MOVEMENT_IMPORT = 'import';
    public const MOVEMENT_EXPORT = 'export';

    public const CUSTOMER_RETAIL = 'retail';
    public const CUSTOMER_AGENCY = 'agency';

    public const EXPORT_NORMAL = 'normal';
    public const EXPORT_SYSTEM = 'system';

    private function __construct()
    {
    }
}
