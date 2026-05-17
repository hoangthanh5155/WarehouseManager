<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    public const TYPE_IMPORT = 'import';
    public const TYPE_EXPORT = 'export';

    protected $guarded = [];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function importVoucher()
    {
        return $this->belongsTo(ImportVoucher::class);
    }

    public function exportVoucher()
    {
        return $this->belongsTo(ExportVoucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
