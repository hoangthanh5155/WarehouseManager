<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FulfillmentOrderSerial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'prepared_at' => 'datetime',
            'delivered_at' => 'datetime',
            'released_at' => 'datetime',
        ];
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

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }
}
