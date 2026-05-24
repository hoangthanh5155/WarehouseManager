<?php

namespace App\Http\Controllers;

use App\Models\FulfillmentOrder;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalesOrderApprovalController extends Controller
{
    public function index(): View
    {
        $orders = FulfillmentOrder::query()
            ->where('status', WarehouseConstants::FULFILLMENT_PENDING_APPROVAL)
            ->with(['customer', 'customerPortalUser'])
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->withSum('items as total_amount', 'total_amount')
            ->latest()
            ->paginate(15);

        return view('sales.order-approvals.index', compact('orders'));
    }

    public function show(FulfillmentOrder $fulfillmentOrder): View
    {
        abort_unless($fulfillmentOrder->status === WarehouseConstants::FULFILLMENT_PENDING_APPROVAL, 404);

        $fulfillmentOrder->load(['customer', 'customerPortalUser', 'items.productCatalog']);

        return view('sales.order-approvals.show', ['order' => $fulfillmentOrder]);
    }

    public function approve(Request $request, FulfillmentOrder $fulfillmentOrder): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $fulfillmentOrder): void {
                $order = FulfillmentOrder::query()
                    ->whereKey($fulfillmentOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->status !== WarehouseConstants::FULFILLMENT_PENDING_APPROVAL) {
                    throw ValidationException::withMessages(['order' => 'Đơn không còn chờ duyệt.']);
                }

                $order->update([
                    'status' => WarehouseConstants::FULFILLMENT_PENDING,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_reason' => null,
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('sales.order_approvals.index')->with('success', 'Đã duyệt đơn.');
    }

    public function reject(Request $request, FulfillmentOrder $fulfillmentOrder): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            DB::transaction(function () use ($request, $fulfillmentOrder, $validated): void {
                $order = FulfillmentOrder::query()
                    ->whereKey($fulfillmentOrder->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->status !== WarehouseConstants::FULFILLMENT_PENDING_APPROVAL) {
                    throw ValidationException::withMessages(['order' => 'Đơn không còn chờ duyệt.']);
                }

                $order->update([
                    'status' => WarehouseConstants::FULFILLMENT_REJECTED,
                    'rejected_by' => $request->user()->id,
                    'rejected_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'] ?? null,
                ]);
            });
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('sales.order_approvals.index')->with('success', 'Đã từ chối đơn.');
    }
}
