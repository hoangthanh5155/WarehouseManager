<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    protected $fillable = [
        'company_name',
        'tax_code',
        'hotline',
        'address',
        'bank_account',
        'bank_name',
    ];

    public static function current(): ?self
    {
        return static::query()->oldest('id')->first();
    }

    public static function fallbackName(): string
    {
        return config('app.name', 'WMS');
    }
}
