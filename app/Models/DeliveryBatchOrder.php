<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBatchOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function deliveryBatch()
    {
        return $this->belongsTo(DeliveryBatch::class);
    }

    public function fulfillmentOrder()
    {
        return $this->belongsTo(FulfillmentOrder::class);
    }

    public function serials()
    {
        return $this->hasMany(DeliveryBatchSerial::class);
    }
}
