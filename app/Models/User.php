<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_WAREHOUSE_MANAGER = 'warehouse_manager';
    public const ROLE_WAREHOUSE_STAFF = 'warehouse_staff';
    public const ROLE_SALES_STAFF = 'sales_staff';
    public const ROLE_VIEWER = 'viewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'name',
        'display_name',
        'email',
        'password',
        'phone',
        'role',
        'status',
        'created_by',
        'last_login_at',
        'last_login_ip',
        'last_user_agent',
        'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Chủ kho',
            self::ROLE_WAREHOUSE_MANAGER => 'Quản lý kho',
            self::ROLE_WAREHOUSE_STAFF => 'Nhân viên kho',
            self::ROLE_SALES_STAFF => 'Nhân viên bán hàng',
            self::ROLE_VIEWER => 'Chỉ xem',
        ];
    }

    public static function roleIcons(): array
    {
        return [
            self::ROLE_ADMIN => 'bi-shield-lock',
            self::ROLE_WAREHOUSE_MANAGER => 'bi-person-gear',
            self::ROLE_WAREHOUSE_STAFF => 'bi-box-seam',
            self::ROLE_SALES_STAFF => 'bi-receipt-cutoff',
            self::ROLE_VIEWER => 'bi-eye',
        ];
    }

    public function displayName(): string
    {
        return $this->display_name ?: $this->name;
    }

    public function roleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? $this->role;
    }

    public function roleIcon(): string
    {
        return self::roleIcons()[$this->role] ?? 'bi-person';
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isWarehouseManager(): bool
    {
        return $this->role === self::ROLE_WAREHOUSE_MANAGER;
    }

    public function canManageUsers(): bool
    {
        return $this->isAdmin() || $this->isWarehouseManager();
    }

    public function canViewFinancialReports(): bool
    {
        return $this->isAdmin();
    }

    public function canViewCostPrices(): bool
    {
        return $this->isAdmin();
    }

    public function canAccessFullProductDetail(): bool
    {
        return $this->isAdmin();
    }

    public function canImportStock(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_WAREHOUSE_MANAGER,
            self::ROLE_WAREHOUSE_STAFF,
        ], true);
    }

    public function canExportStock(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_WAREHOUSE_MANAGER,
            self::ROLE_WAREHOUSE_STAFF,
            self::ROLE_SALES_STAFF,
        ], true);
    }

    public function canEditExportMetadata(): bool
    {
        return $this->isAdmin() || $this->isWarehouseManager();
    }

    public function canManageSettings(): bool
    {
        return $this->isAdmin();
    }

    public function canManageWarehouseCatalogs(): bool
    {
        return $this->isAdmin() || $this->isWarehouseManager();
    }

    public function canViewOperationsDashboard(): bool
    {
        return $this->isAdmin() || $this->isWarehouseManager();
    }

    public function manageableRoles(): array
    {
        if ($this->isAdmin()) {
            return self::roleLabels();
        }

        if ($this->isWarehouseManager()) {
            return collect(self::roleLabels())->only([
                self::ROLE_WAREHOUSE_STAFF,
                self::ROLE_SALES_STAFF,
                self::ROLE_VIEWER,
            ])->all();
        }

        return [];
    }

    public function canAssignRole(string $role): bool
    {
        return array_key_exists($role, $this->manageableRoles());
    }

    public function canManageUser(self $target): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isWarehouseManager()) {
            return in_array($target->role, [
                self::ROLE_WAREHOUSE_STAFF,
                self::ROLE_SALES_STAFF,
                self::ROLE_VIEWER,
            ], true);
        }

        return false;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
