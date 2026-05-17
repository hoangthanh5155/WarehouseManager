<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Mở khóa toàn bộ các cột để lưu dữ liệu hàng loạt không bị lỗi fillable
    protected $guarded = [];

    protected $casts = [
        'imported_at' => 'datetime',
        'exported_at' => 'datetime',
    ];

    // Khai báo để Laravel hiểu khi gọi $product->supplier
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Khai báo để Laravel hiểu khi gọi $product->location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    // Khai báo để Laravel hiểu khi gọi $product->productCatalog
    public function productCatalog()
    {
        return $this->belongsTo(ProductCatalog::class, 'product_catalog_id');
    }

    public function importVoucher()
    {
        return $this->belongsTo(ImportVoucher::class);
    }

    public function exportVoucher()
    {
        return $this->belongsTo(ExportVoucher::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
