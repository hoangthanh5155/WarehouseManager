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

    public const ORDER_TYPE_SYSTEM = 'system';
    public const ORDER_TYPE_MANUAL = 'manual';
    public const ORDER_TYPE_GUEST = 'guest';

    public const FULFILLMENT_PENDING_APPROVAL = 'pending_approval';
    public const FULFILLMENT_PENDING = 'pending';
    public const FULFILLMENT_REJECTED = 'rejected';
    public const FULFILLMENT_RESERVED = 'reserved';
    public const FULFILLMENT_IN_DELIVERY = 'in_delivery';
    public const FULFILLMENT_DELIVERED = 'delivered';
    public const FULFILLMENT_FAILED = 'failed';
    public const FULFILLMENT_CANCELLED = 'cancelled';

    public const DELIVERY_BATCH_DRAFT = 'draft';
    public const DELIVERY_BATCH_PICKING = 'picking';
    public const DELIVERY_BATCH_READY = 'ready';
    public const DELIVERY_BATCH_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const DELIVERY_BATCH_COMPLETED = 'completed';
    public const DELIVERY_BATCH_CANCELLED = 'cancelled';

    public const DELIVERY_ORDER_PENDING = 'pending';
    public const DELIVERY_ORDER_PICKING = 'picking';
    public const DELIVERY_ORDER_READY = 'ready';
    public const DELIVERY_ORDER_DELIVERED = 'delivered';
    public const DELIVERY_ORDER_FAILED = 'failed';
    public const DELIVERY_ORDER_CANCELLED = 'cancelled';

    public const DELIVERY_SERIAL_RESERVED = 'reserved';
    public const DELIVERY_SERIAL_ASSIGNED = 'assigned';
    public const DELIVERY_SERIAL_DELIVERED = 'delivered';
    public const DELIVERY_SERIAL_RELEASED = 'released';

    private function __construct()
    {
    }
}
