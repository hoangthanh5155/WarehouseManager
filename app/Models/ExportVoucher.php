<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'export_code',
        'export_type',
        'customer_type',
        'seller_name',
        'seller_tax_code',
        'seller_address',
        'seller_phone',
        'seller_bank_account',
        'seller_bank_name',
        'customer_id',
        'buyer_name',
        'company_name',
        'address',
        'tax_code',
        'items',
        'total_cost',
        'total_amount',
        'note',
        'exported_at'
    ];

    // 💡 ĐOẠN NÀY BẮT BUỘC PHẢI CÓ để không bị lỗi 500 khi lưu mảng hàng hóa
    protected $casts = [
        'items' => 'array',
        'exported_at' => 'datetime'
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
}
