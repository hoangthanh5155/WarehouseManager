<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    public const ROLE_ACCOUNTANT = 'accountant';
    public const ROLE_VIEWER = 'viewer';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_LOCKED = 'locked';

    public const ABILITY_CREATE_SALES_ORDERS = 'create_sales_orders';
    public const ABILITY_APPROVE_CUSTOMER_ORDERS = 'approve_customer_orders';
    public const ABILITY_VIEW_FINANCIAL_REPORTS = 'view_financial_reports';
    public const ABILITY_VIEW_COST_PRICES = 'view_cost_prices';
    public const ABILITY_VIEW_WAREHOUSE_REPORTS = 'view_warehouse_reports';
    public const ABILITY_VIEW_WAREHOUSE_HISTORY = 'view_warehouse_history';
    public const ABILITY_TRACE_SERIAL = 'trace_serial';
    public const ABILITY_MANAGE_CASHFLOW = 'manage_cashflow';

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
            self::ROLE_ACCOUNTANT => 'Kế toán',
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
            self::ROLE_ACCOUNTANT => 'bi-cash-coin',
            self::ROLE_VIEWER => 'bi-eye',
        ];
    }

    public static function featurePermissionLabels(): array
    {
        return [
            self::ABILITY_CREATE_SALES_ORDERS => 'Tạo đơn bán hàng',
            self::ABILITY_APPROVE_CUSTOMER_ORDERS => 'Duyệt đơn khách hàng',
            self::ABILITY_VIEW_FINANCIAL_REPORTS => 'Xem báo cáo tài chính',
            self::ABILITY_VIEW_COST_PRICES => 'Xem giá vốn/lợi nhuận',
            self::ABILITY_VIEW_WAREHOUSE_REPORTS => 'Xem nhập xuất tồn',
            self::ABILITY_VIEW_WAREHOUSE_HISTORY => 'Xem lịch sử kho',
            self::ABILITY_TRACE_SERIAL => 'Truy vết Serial',
            self::ABILITY_MANAGE_CASHFLOW => 'Quản lý thu chi',
        ];
    }

    public function featurePermissions(): HasMany
    {
        return $this->hasMany(UserFeaturePermission::class);
    }

    public function featurePermissionAbilities(): array
    {
        if ($this->relationLoaded('featurePermissions')) {
            return $this->featurePermissions->pluck('ability')->all();
        }

        return $this->featurePermissions()->pluck('ability')->all();
    }

    public function hasFeaturePermission(string $ability): bool
    {
        if (!$this->canReceiveFeaturePermissions()) {
            return false;
        }

        return in_array($ability, $this->featurePermissionAbilities(), true);
    }

    public function canReceiveFeaturePermissions(): bool
    {
        return in_array($this->role, [
            self::ROLE_WAREHOUSE_MANAGER,
            self::ROLE_ACCOUNTANT,
        ], true);
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
        return $this->isAdmin()
            || $this->role === self::ROLE_ACCOUNTANT
            || $this->hasFeaturePermission(self::ABILITY_VIEW_FINANCIAL_REPORTS);
    }

    public function canViewWarehouseReports(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_WAREHOUSE_MANAGER,
            self::ROLE_WAREHOUSE_STAFF,
            self::ROLE_ACCOUNTANT,
        ], true) || $this->hasFeaturePermission(self::ABILITY_VIEW_WAREHOUSE_REPORTS);
    }

    public function canViewWarehouseHistory(): bool
    {
        return $this->canViewWarehouseReports()
            || $this->hasFeaturePermission(self::ABILITY_VIEW_WAREHOUSE_HISTORY);
    }

    public function canTraceSerial(): bool
    {
        return $this->canViewWarehouseReports()
            || $this->hasFeaturePermission(self::ABILITY_TRACE_SERIAL);
    }

    public function canViewCostPrices(): bool
    {
        return $this->isAdmin()
            || $this->role === self::ROLE_ACCOUNTANT
            || $this->hasFeaturePermission(self::ABILITY_VIEW_COST_PRICES);
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

    public function canManageMasterData(): bool
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

    public function canCreateSalesOrders(): bool
    {
        return $this->isAdmin()
            || in_array($this->role, [self::ROLE_SALES_STAFF, self::ROLE_ACCOUNTANT], true)
            || $this->hasFeaturePermission(self::ABILITY_CREATE_SALES_ORDERS);
    }

    public function canApproveCustomerOrders(): bool
    {
        return $this->isAdmin()
            || $this->isWarehouseManager()
            || $this->hasFeaturePermission(self::ABILITY_APPROVE_CUSTOMER_ORDERS);
    }

    public function canManageCashflow(): bool
    {
        return $this->isAdmin()
            || $this->role === self::ROLE_ACCOUNTANT
            || $this->hasFeaturePermission(self::ABILITY_MANAGE_CASHFLOW);
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
