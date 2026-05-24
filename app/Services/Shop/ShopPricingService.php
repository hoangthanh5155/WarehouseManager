<?php

namespace App\Services\Shop;

use App\Models\CustomerPortalUser;
use App\Models\ProductCatalog;

class ShopPricingService
{
    public function priceFor(ProductCatalog $catalog, ?CustomerPortalUser $customerUser = null): float
    {
        if ($customerUser?->canSeeAgencyPrice()) {
            return (float) $catalog->agency_price;
        }

        return (float) $catalog->retail_price;
    }

    public function customerTypeFor(?CustomerPortalUser $customerUser = null): string
    {
        return $customerUser?->canSeeAgencyPrice() ? 'agency' : 'retail';
    }

    public function priceLabelFor(?CustomerPortalUser $customerUser = null): string
    {
        return $customerUser?->canSeeAgencyPrice() ? 'Giá đại lý' : 'Giá bán lẻ';
    }
}
