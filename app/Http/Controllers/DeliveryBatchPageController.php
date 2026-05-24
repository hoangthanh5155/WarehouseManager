<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\DeliveryBatch;
use App\Models\FulfillmentOrder;
use App\Models\ProductCatalog;
use App\Support\Warehouse\WarehouseConstants;

class DeliveryBatchPageController extends Controller
{
    public function ordersIndex()
    {
        $orders = FulfillmentOrder::query()
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->withSum('items as total_amount', 'total_amount')
            ->latest()
            ->paginate(15);

        return view('delivery.orders.index', compact('orders'));
    }

    public function ordersCreate()
    {
        $customers = Customer::query()->orderBy('name')->get();
        $productCatalogs = ProductCatalog::query()
            ->withCount(['products as stock_count' => fn ($query) => $query->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)])
            ->orderBy('product_name')
            ->get();

        return view('delivery.orders.create', compact('customers', 'productCatalogs'));
    }

    public function batchesIndex()
    {
        $batches = DeliveryBatch::query()
            ->withCount(['batchOrders', 'serials'])
            ->latest()
            ->paginate(15);

        return view('delivery.batches.index', compact('batches'));
    }

    public function batchesShow(DeliveryBatch $deliveryBatch)
    {
        $deliveryBatch->load([
            'batchOrders.fulfillmentOrder.items.productCatalog',
            'serials.productCatalog',
            'serials.deliveryBatchOrder.fulfillmentOrder',
        ]);

        $availableOrders = FulfillmentOrder::query()
            ->whereNotIn('status', [
                WarehouseConstants::FULFILLMENT_DELIVERED,
                WarehouseConstants::FULFILLMENT_CANCELLED,
            ])
            ->whereDoesntHave('batchOrders', fn ($query) => $query->where('delivery_batch_id', $deliveryBatch->id))
            ->withSum('items as total_quantity', 'quantity')
            ->latest()
            ->limit(50)
            ->get();

        return view('delivery.batches.show', [
            'batch' => $deliveryBatch,
            'availableOrders' => $availableOrders,
        ]);
    }
}
