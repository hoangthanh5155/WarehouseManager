<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPortalUser;
use App\Models\FulfillmentOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ShopAccountController extends Controller
{
    public function showRegister()
    {
        return view('shop.auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customer_portal_users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::query()->create([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'type' => CustomerPortalUser::CUSTOMER_RETAIL,
        ]);

        $user = CustomerPortalUser::query()->create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'account_type' => CustomerPortalUser::ACCOUNT_RETAIL,
            'customer_type' => CustomerPortalUser::CUSTOMER_RETAIL,
            'approval_status' => CustomerPortalUser::APPROVAL_APPROVED,
            'is_active' => true,
        ]);

        Auth::guard('customer')->login($user);
        $request->session()->regenerate();

        return redirect()->route('shop.account')->with('success', 'Đăng ký tài khoản thành công.');
    }

    public function showLogin()
    {
        return view('shop.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email hoặc mật khẩu không đúng.']);
        }

        $user = Auth::guard('customer')->user();
        if (!$user->is_active || $user->approval_status === CustomerPortalUser::APPROVAL_REJECTED) {
            Auth::guard('customer')->logout();
            $request->session()->regenerateToken();
            throw ValidationException::withMessages(['email' => 'Tài khoản không còn hoạt động.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('shop.account'));
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('shop.index');
    }

    public function account()
    {
        return view('shop.account.index', ['customerUser' => Auth::guard('customer')->user()]);
    }

    public function orders()
    {
        $orders = Auth::guard('customer')->user()
            ->fulfillmentOrders()
            ->withSum('items as total_amount', 'total_amount')
            ->latest()
            ->paginate(15);

        return view('shop.account.orders', compact('orders'));
    }

    public function orderShow(FulfillmentOrder $fulfillmentOrder)
    {
        $customerUser = Auth::guard('customer')->user();
        abort_unless((int) $fulfillmentOrder->customer_portal_user_id === (int) $customerUser->id, 403);

        $fulfillmentOrder->load('items.productCatalog');

        return view('shop.account.order-show', ['order' => $fulfillmentOrder]);
    }
}
