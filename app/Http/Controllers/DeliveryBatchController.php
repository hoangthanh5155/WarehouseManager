<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsWithApi;
use App\Models\DeliveryBatch;
use App\Models\DeliveryBatchOrder;
use App\Models\FulfillmentOrder;
use App\Services\Warehouse\DeliveryBatchSerialService;
use App\Services\Warehouse\DeliveryBatchService;
use App\Services\Warehouse\DeliveryOrderFulfillmentService;
use App\Services\Warehouse\FulfillmentOrderService;
use App\Support\Warehouse\WarehouseConstants;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class DeliveryBatchController extends Controller
{
    use RespondsWithApi;

    public function storeOrder(Request $request, FulfillmentOrderService $service)
    {
        $validator = Validator::make($request->all(), [
            'order_type' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'integer'],
            'customer_type' => ['nullable', 'string'],
            'buyer_name' => ['required', 'string'],
            'company_name' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'tax_code' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu don giao khong hop le.', $validator->errors(), 422);
        }

        try {
            $payload = $validator->validated() + $request->all();
            $payload['status'] = WarehouseConstants::FULFILLMENT_PENDING;
            $order = $service->create($payload, $request->user()?->id);

            return $this->successResponse('Da tao don can giao.', $order);
        } catch (ValidationException $e) {
            return $this->errorResponse('Du lieu don giao khong hop le.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi tao don giao: ' . $e->getMessage(), [], 500);
        }
    }

    public function storeBatch(Request $request, DeliveryBatchService $service)
    {
        $validator = Validator::make($request->all(), [
            'batch_code' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu chuyen giao khong hop le.', $validator->errors(), 422);
        }

        try {
            $batch = $service->create($validator->validated(), $request->user()?->id);

            return $this->successResponse('Da tao chuyen giao.', $batch);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi tao chuyen giao: ' . $e->getMessage(), [], 500);
        }
    }

    public function addOrder(Request $request, DeliveryBatch $deliveryBatch, DeliveryBatchService $service)
    {
        $validator = Validator::make($request->all(), [
            'fulfillment_order_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu them don vao chuyen khong hop le.', $validator->errors(), 422);
        }

        try {
            $order = FulfillmentOrder::query()->findOrFail((int) $request->input('fulfillment_order_id'));
            $batchOrder = $service->addOrder($deliveryBatch, $order);

            return $this->successResponse('Da them don vao chuyen giao.', $batchOrder);
        } catch (ValidationException $e) {
            return $this->errorResponse('Khong the them don vao chuyen.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi them don vao chuyen: ' . $e->getMessage(), [], 500);
        }
    }

    public function reserveSerials(Request $request, DeliveryBatch $deliveryBatch, DeliveryBatchSerialService $service)
    {
        $validator = Validator::make($request->all(), [
            'serials' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu giu serial khong hop le.', $validator->errors(), 422);
        }

        try {
            $reservations = $service->reserveSerials($deliveryBatch, $request->input('serials', []), $request->user()?->id);

            return $this->successResponse('Da giu serial cho chuyen giao.', $reservations);
        } catch (ValidationException $e) {
            return $this->errorResponse('Khong the giu serial.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi giu serial: ' . $e->getMessage(), [], 500);
        }
    }

    public function assignOrderSerials(Request $request, DeliveryBatchOrder $deliveryBatchOrder, DeliveryBatchSerialService $service)
    {
        $validator = Validator::make($request->all(), [
            'serials' => ['required', 'array', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu xac minh serial khong hop le.', $validator->errors(), 422);
        }

        try {
            $assigned = $service->assignSerialsToOrder($deliveryBatchOrder, $request->input('serials', []), $request->user()?->id);

            return $this->successResponse('Da xac minh serial cho don.', $assigned);
        } catch (ValidationException $e) {
            return $this->errorResponse('Khong the xac minh serial.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi xac minh serial: ' . $e->getMessage(), [], 500);
        }
    }

    public function deliverOrder(Request $request, DeliveryBatchOrder $deliveryBatchOrder, DeliveryBatchSerialService $serialService, DeliveryOrderFulfillmentService $service)
    {
        $validator = Validator::make($request->all(), [
            'serials' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu xac nhan serial khong hop le.', $validator->errors(), 422);
        }

        try {
            $serials = $request->input('serials', []);
            if (is_string($serials)) {
                $serials = preg_split('/\R+/', $serials) ?: [];
            }

            $serialService->assignSerialsToOrder($deliveryBatchOrder, $serials, $request->user()?->id);
            $result = $service->deliver($deliveryBatchOrder, request()->user()?->id);

            return $this->successResponse('Giao don thanh cong, da tao phieu xuat kho.', $result);
        } catch (ValidationException $e) {
            return $this->errorResponse('Khong the giao don.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi giao don: ' . $e->getMessage(), [], 500);
        }
    }

    public function failOrder(Request $request, DeliveryBatchOrder $deliveryBatchOrder, DeliveryOrderFulfillmentService $service)
    {
        try {
            $service->fail($deliveryBatchOrder, $request->input('note'), $request->user()?->id);

            return $this->successResponse('Da danh dau giao that bai.', [
                'delivery_batch_order_id' => $deliveryBatchOrder->id,
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi danh dau giao that bai: ' . $e->getMessage(), [], 500);
        }
    }
}
