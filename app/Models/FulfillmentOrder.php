<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentOrder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'prepared_at' => 'datetime',
            'printed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

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

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function items()
    {
        return $this->hasMany(FulfillmentOrderItem::class);
    }

    public function preparedSerials()
    {
        return $this->hasMany(FulfillmentOrderSerial::class);
    }

    public function exportVoucher()
    {
        return $this->belongsTo(ExportVoucher::class);
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
