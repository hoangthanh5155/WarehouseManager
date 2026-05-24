<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentOrderItem extends Model
{
    protected $guarded = [];

    public function fulfillmentOrder()
    {
        return $this->belongsTo(FulfillmentOrder::class);
    }

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }

    public function deliverySerials()
    {
        return $this->hasMany(DeliveryBatchSerial::class);
    }
}
