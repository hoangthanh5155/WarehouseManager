<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentOrder extends Model
{
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerPortalUser()
    {
        return $this->belongsTo(CustomerPortalUser::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(FulfillmentOrderItem::class);
    }

    public function batchOrders()
    {
        return $this->hasMany(DeliveryBatchOrder::class);
    }

    public function serials()
    {
        return $this->hasMany(DeliveryBatchSerial::class);
    }
}
