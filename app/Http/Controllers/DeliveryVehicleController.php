<?php

namespace App\Http\Controllers;

use App\Models\DeliveryVehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryVehicleController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canManageDeliveryVehicles(), 403);

        return view('delivery.vehicles.index', [
            'vehicles' => DeliveryVehicle::query()->withCount('batches')->latest()->paginate(15),
            'typeLabels' => DeliveryVehicle::typeLabels(),
            'statusLabels' => DeliveryVehicle::statusLabels(),
        ]);
    }

    public function create(): View
    {
        abort_unless(auth()->user()?->canManageDeliveryVehicles(), 403);

        return view('delivery.vehicles.create', [
            'vehicle' => new DeliveryVehicle(['status' => DeliveryVehicle::STATUS_ACTIVE]),
            'typeLabels' => DeliveryVehicle::typeLabels(),
            'statusLabels' => DeliveryVehicle::statusLabels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->canManageDeliveryVehicles(), 403);

        $validated = $this->validatePayload($request);
        $validated['created_by'] = $request->user()?->id;
        $validated['updated_by'] = $request->user()?->id;

        DeliveryVehicle::query()->create($validated);

        return redirect()->route('delivery.vehicles.index')->with('success', 'Đã tạo phương tiện giao hàng.');
    }

    public function edit(DeliveryVehicle $deliveryVehicle): View
    {
        abort_unless(auth()->user()?->canManageDeliveryVehicles(), 403);

        return view('delivery.vehicles.edit', [
            'vehicle' => $deliveryVehicle,
            'typeLabels' => DeliveryVehicle::typeLabels(),
            'statusLabels' => DeliveryVehicle::statusLabels(),
        ]);
    }

    public function update(Request $request, DeliveryVehicle $deliveryVehicle): RedirectResponse
    {
        abort_unless($request->user()?->canManageDeliveryVehicles(), 403);

        $validated = $this->validatePayload($request, $deliveryVehicle);
        $validated['updated_by'] = $request->user()?->id;

        $deliveryVehicle->update($validated);

        return redirect()->route('delivery.vehicles.index')->with('success', 'Đã cập nhật phương tiện giao hàng.');
    }

    public function destroy(DeliveryVehicle $deliveryVehicle): RedirectResponse
    {
        abort_unless(auth()->user()?->canManageDeliveryVehicles(), 403);

        if ($deliveryVehicle->batches()->exists()) {
            $deliveryVehicle->update([
                'status' => DeliveryVehicle::STATUS_INACTIVE,
                'updated_by' => auth()->id(),
            ]);

            return back()->with('success', 'Phương tiện đã từng dùng trong chuyến, đã chuyển sang ngưng sử dụng.');
        }

        $deliveryVehicle->delete();

        return redirect()->route('delivery.vehicles.index')->with('success', 'Đã xóa phương tiện giao hàng.');
    }

    private function validatePayload(Request $request, ?DeliveryVehicle $vehicle = null): array
    {
        return $request->validate([
            'vehicle_type' => ['required', Rule::in(array_keys(DeliveryVehicle::typeLabels()))],
            'plate_number' => [
                Rule::requiredIf(fn () => $request->input('vehicle_type') === DeliveryVehicle::TYPE_CAR),
                'nullable',
                'string',
                'max:50',
                Rule::unique('delivery_vehicles', 'plate_number')->ignore($vehicle?->id),
            ],
            'load_capacity' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(DeliveryVehicle::statusLabels()))],
            'note' => ['nullable', 'string'],
        ]);
    }
}
