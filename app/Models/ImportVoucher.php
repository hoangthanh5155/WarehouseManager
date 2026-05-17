<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportVoucher extends Model
{
    protected $guarded = [];

    protected $casts = [
        'imported_at' => 'datetime',
        'wholesale_price' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
