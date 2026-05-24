<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CustomerPortalUser extends Authenticatable
{
    use Notifiable;

    public const ACCOUNT_RETAIL = 'retail';
    public const ACCOUNT_STORE = 'store';

    public const CUSTOMER_RETAIL = 'retail';
    public const CUSTOMER_AGENCY = 'agency';

    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_REJECTED = 'rejected';

    protected $fillable = [
        'customer_id',
        'name',
        'email',
        'password',
        'phone',
        'account_type',
        'customer_type',
        'approval_status',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function fulfillmentOrders()
    {
        return $this->hasMany(FulfillmentOrder::class);
    }

    public function canSeeAgencyPrice(): bool
    {
        return $this->is_active
            && $this->account_type === self::ACCOUNT_STORE
            && $this->customer_type === self::CUSTOMER_AGENCY
            && $this->approval_status === self::APPROVAL_APPROVED;
    }
}
