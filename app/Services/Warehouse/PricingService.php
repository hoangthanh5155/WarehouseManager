<?php

namespace App\Services\Warehouse;

class PricingService
{
    public function calculate(float|int|string|null $wholesalePrice, float|int|string|null $agencyMargin, float|int|string|null $profitMargin): array
    {
        $wholesale = (float) ($wholesalePrice ?? 0);
        $agency = (float) ($agencyMargin ?? 0);
        $profit = (float) ($profitMargin ?? 0);

        return [
            'wholesale_price' => $wholesale,
            'agency_margin' => $agency,
            'profit_margin' => $profit,
            'agency_price' => $wholesale * (1 + ($agency / 100)),
            'retail_price' => $wholesale * (1 + ($profit / 100)),
        ];
    }
}
