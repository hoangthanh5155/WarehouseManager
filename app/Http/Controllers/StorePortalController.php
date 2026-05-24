<?php

namespace App\Http\Controllers;

use App\Models\ProductCatalog;
use App\Services\Shop\ShopPricingService;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Support\Facades\Auth;

class StorePortalController extends Controller
{
    public function __construct(protected ShopPricingService $pricingService)
    {
    }

    public function dashboard()
    {
        return view('store.dashboard', [
            'customerUser' => Auth::guard('customer')->user(),
        ]);
    }

    public function products()
    {
        $customerUser = Auth::guard('customer')->user();
        $productCatalogs = ProductCatalog::query()
            ->withCount(['products as stock_count' => fn ($query) => $query
                ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
                ->whereDoesntHave('activeFulfillmentReservation')
                ->whereDoesntHave('activeDeliveryReservation')])
            ->orderBy('product_name')
            ->paginate(12);

        return view('store.products', [
            'customerUser' => $customerUser,
            'productCatalogs' => $productCatalogs,
            'pricingService' => $this->pricingService,
        ]);
    }
}
