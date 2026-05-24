<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportVoucherItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_cost' => 'float',
        'total_cost' => 'float',
    ];

    public function importVoucher()
    {
        return $this->belongsTo(ImportVoucher::class);
    }

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
