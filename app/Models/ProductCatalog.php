<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCatalog extends Model
{
    protected $guarded = [];

    /**
     * Mối quan hệ: Một danh mục mẫu sản phẩm có nhiều sản phẩm cụ thể trong kho.
     * Giúp hệ thống lấy thông tin vị trí kệ từ bảng products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'product_catalog_id');
    }

    /**
     * Mối quan hệ: Một danh mục mẫu sản phẩm thuộc về một nhà cung cấp.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}