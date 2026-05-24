<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExportVoucherItemSerial extends Model
{
    protected $guarded = [];

    public function exportVoucherItem()
    {
        return $this->belongsTo(ExportVoucherItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
