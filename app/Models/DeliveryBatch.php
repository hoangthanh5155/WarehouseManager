<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBatch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batchOrders()
    {
        return $this->hasMany(DeliveryBatchOrder::class);
    }

    public function fulfillmentOrders()
    {
        return $this->belongsToMany(FulfillmentOrder::class, 'delivery_batch_orders')
            ->withPivot(['status', 'delivered_at', 'failed_at', 'note'])
            ->withTimestamps();
    }

    public function serials()
    {
        return $this->hasMany(DeliveryBatchSerial::class);
    }
}
