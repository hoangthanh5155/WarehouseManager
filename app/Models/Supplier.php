<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $guarded = []; // Chống Mass Assignment nhanh, tiện lợi cho việc nhập liệu

    /**
     * Mối quan hệ: Một nhà cung cấp có nhiều sản phẩm.
     * Liên kết với bảng `products` qua khóa ngoại `supplier_id`.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }
}