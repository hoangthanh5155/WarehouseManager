<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerPortalUser;
use Illuminate\Http\Request;

class CustomerPortalUserController extends Controller
{
    public function customers()
    {
        $customers = Customer::query()
            ->withCount('fulfillmentOrders')
            ->latest()
            ->paginate(20);

        return view('sales.customers.index', compact('customers'));
    }

    public function index()
    {
        $accounts = CustomerPortalUser::query()
            ->with('customer')
            ->latest()
            ->paginate(20);

        return view('sales.customer-accounts.index', compact('accounts'));
    }

    public function edit(CustomerPortalUser $customerPortalUser)
    {
        $customers = Customer::query()->orderBy('name')->get();

        return view('sales.customer-accounts.edit', compact('customerPortalUser', 'customers'));
    }

    public function update(Request $request, CustomerPortalUser $customerPortalUser)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'account_type' => ['required', 'in:retail,store'],
            'customer_type' => ['required', 'in:retail,agency'],
            'approval_status' => ['required', 'in:pending,approved,rejected'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $customerPortalUser->update($validated);

        return redirect()
            ->route('sales.customer_accounts.index')
            ->with('success', 'Đã cập nhật tài khoản khách hàng.');
    }
}
