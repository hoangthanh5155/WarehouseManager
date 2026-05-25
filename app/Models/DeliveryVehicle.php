<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryVehicle extends Model
{
    public const TYPE_MOTORCYCLE = 'motorcycle';
    public const TYPE_CAR = 'car';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $guarded = [];

    public static function typeLabels(): array
    {
        return [
            self::TYPE_MOTORCYCLE => 'Xe máy',
            self::TYPE_CAR => 'Ô tô',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_ACTIVE => 'Đang sử dụng',
            self::STATUS_INACTIVE => 'Ngưng sử dụng',
        ];
    }

    public function batches()
    {
        return $this->hasMany(DeliveryBatch::class, 'vehicle_id');
    }

    public function displayName(): string
    {
        $type = self::typeLabels()[$this->vehicle_type] ?? $this->vehicle_type;
        return trim($type . ($this->plate_number ? ' - ' . $this->plate_number : ''));
    }
}
