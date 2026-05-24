<?php

namespace App\Http\Controllers;

use App\Models\ProductCatalog;
use App\Services\Shop\ShopPricingService;
use App\Services\Warehouse\FulfillmentOrderService;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function __construct(protected ShopPricingService $pricingService)
    {
    }

    public function index()
    {
        $customerUser = Auth::guard('customer')->user();
        $productCatalogs = ProductCatalog::query()
            ->withCount(['products as stock_count' => fn ($query) => $query
                ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
                ->whereDoesntHave('activeFulfillmentReservation')
                ->whereDoesntHave('activeDeliveryReservation')])
            ->orderBy('product_name')
            ->paginate(12);

        return view('shop.index', [
            'productCatalogs' => $productCatalogs,
            'customerUser' => $customerUser,
            'pricingService' => $this->pricingService,
        ]);
    }

    public function show(ProductCatalog $productCatalog)
    {
        $customerUser = Auth::guard('customer')->user();
        $productCatalog->loadCount(['products as stock_count' => fn ($query) => $query
            ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
            ->whereDoesntHave('activeFulfillmentReservation')
            ->whereDoesntHave('activeDeliveryReservation')]);

        return view('shop.show', [
            'productCatalog' => $productCatalog,
            'customerUser' => $customerUser,
            'pricingService' => $this->pricingService,
        ]);
    }

    public function cart(Request $request)
    {
        return view('shop.cart', $this->cartViewData($request));
    }

    public function addToCart(Request $request)
    {
        $validated = $request->validate([
            'product_catalog_id' => ['required', 'integer', 'exists:product_catalogs,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = $request->session()->get('shop_cart', []);
        $catalogId = (int) $validated['product_catalog_id'];
        $cart[$catalogId] = ($cart[$catalogId] ?? 0) + (int) $validated['quantity'];
        $request->session()->put('shop_cart', $cart);

        return back()->with('success', 'Đã thêm sản phẩm vào giỏ.');
    }

    public function updateCart(Request $request)
    {
        $items = $request->input('items', []);
        $cart = [];

        foreach ($items as $catalogId => $quantity) {
            $quantity = (int) $quantity;
            if ($quantity > 0) {
                $cart[(int) $catalogId] = min($quantity, 99);
            }
        }

        $request->session()->put('shop_cart', $cart);

        return redirect()->route('shop.cart')->with('success', 'Đã cập nhật giỏ hàng.');
    }

    public function removeFromCart(Request $request)
    {
        $catalogId = (int) $request->input('product_catalog_id');
        $cart = $request->session()->get('shop_cart', []);
        unset($cart[$catalogId]);
        $request->session()->put('shop_cart', $cart);

        return redirect()->route('shop.cart')->with('success', 'Đã xóa sản phẩm khỏi giỏ.');
    }

    public function checkout(Request $request)
    {
        $data = $this->cartViewData($request);
        if ($data['items']->isEmpty()) {
            return redirect()->route('shop.cart')->with('error', 'Giỏ hàng đang trống.');
        }

        return view('shop.checkout', $data + [
            'customerUser' => Auth::guard('customer')->user(),
        ]);
    }

    public function storeCheckout(Request $request, FulfillmentOrderService $orderService)
    {
        $validated = $request->validate([
            'buyer_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $cartData = $this->cartViewData($request);
        if ($cartData['items']->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
        }

        $customerUser = Auth::guard('customer')->user();
        $customerType = $this->pricingService->customerTypeFor($customerUser);
        $order = $orderService->create([
            'order_type' => $customerUser ? WarehouseConstants::ORDER_TYPE_SYSTEM : WarehouseConstants::ORDER_TYPE_GUEST,
            'customer_id' => $customerUser?->customer_id,
            'customer_portal_user_id' => $customerUser?->id,
            'customer_type' => $customerType,
            'buyer_name' => $validated['buyer_name'],
            'phone' => $validated['phone'] ?? $customerUser?->phone,
            'address' => $validated['address'],
            'status' => WarehouseConstants::FULFILLMENT_PENDING_APPROVAL,
            'note' => $validated['note'] ?? null,
            'create_customer' => false,
            'items' => $cartData['items']->map(fn ($item) => [
                'product_catalog_id' => $item['catalog']->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ])->values()->all(),
        ], null);

        $request->session()->forget('shop_cart');

        return view('shop.checkout-success', compact('order'));
    }

    private function cartViewData(Request $request): array
    {
        $customerUser = Auth::guard('customer')->user();
        $cart = collect($request->session()->get('shop_cart', []))
            ->mapWithKeys(fn ($quantity, $catalogId) => [(int) $catalogId => (int) $quantity])
            ->filter(fn ($quantity) => $quantity > 0);

        $catalogs = ProductCatalog::query()
            ->whereIn('id', $cart->keys())
            ->withCount(['products as stock_count' => fn ($query) => $query
                ->where('status', WarehouseConstants::PRODUCT_STATUS_IN_STOCK)
                ->whereDoesntHave('activeFulfillmentReservation')
                ->whereDoesntHave('activeDeliveryReservation')])
            ->get()
            ->keyBy('id');

        $items = $cart->map(function (int $quantity, int $catalogId) use ($catalogs, $customerUser) {
            $catalog = $catalogs->get($catalogId);
            if (!$catalog) {
                return null;
            }

            $unitPrice = $this->pricingService->priceFor($catalog, $customerUser);

            return [
                'catalog' => $catalog,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $quantity,
            ];
        })->filter()->values();

        return [
            'items' => $items,
            'totalAmount' => $items->sum('total_amount'),
            'priceLabel' => $this->pricingService->priceLabelFor($customerUser),
        ];
    }
}
