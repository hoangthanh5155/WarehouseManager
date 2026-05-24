<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    // Khai báo các trường cho phép lưu hàng loạt (Mass Assignment)
    protected $fillable = [
        'name',
        'company_name',
        'address',
        'tax_code',
        'phone',
        'type'
    ];

    /**
     * Mối quan hệ: Một khách hàng có thể có nhiều phiếu xuất kho
     */
    public function exportVouchers()
    {
        return $this->hasMany(ExportVoucher::class);
    }

    public function fulfillmentOrders()
    {
        return $this->hasMany(FulfillmentOrder::class);
    }

    public function portalUsers()
    {
        return $this->hasMany(CustomerPortalUser::class);
    }
}
