<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBatchSerial extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reserved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'delivered_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    public function deliveryBatch()
    {
        return $this->belongsTo(DeliveryBatch::class);
    }

    public function deliveryBatchOrder()
    {
        return $this->belongsTo(DeliveryBatchOrder::class);
    }

    public function fulfillmentOrder()
    {
        return $this->belongsTo(FulfillmentOrder::class);
    }

    public function fulfillmentOrderItem()
    {
        return $this->belongsTo(FulfillmentOrderItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function activeProduct()
    {
        return $this->belongsTo(Product::class, 'active_product_id');
    }

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }
}
