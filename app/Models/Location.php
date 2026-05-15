<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $guarded = [];

    /**
     * Mối quan hệ: Một vị trí kệ chứa nhiều sản phẩm cụ thể trong kho.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'location_id');
    }
}