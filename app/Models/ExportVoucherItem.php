<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportVoucherItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'unit_cost' => 'float',
        'unit_price' => 'float',
        'total_cost' => 'float',
        'total_amount' => 'float',
    ];

    public function exportVoucher()
    {
        return $this->belongsTo(ExportVoucher::class);
    }

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }

    public function serials()
    {
        return $this->hasMany(ExportVoucherItemSerial::class);
    }

    public function products()
    {
        return $this->hasManyThrough(
            Product::class,
            ExportVoucherItemSerial::class,
            'export_voucher_item_id',
            'id',
            'id',
            'product_id'
        );
    }
}
