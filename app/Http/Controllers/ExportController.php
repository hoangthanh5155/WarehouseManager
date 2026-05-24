<?php

namespace App\Http\Controllers;

use App\Http\Concerns\RespondsWithApi;
use App\Models\Customer;
use App\Models\ExportVoucher;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Services\Warehouse\ExportStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class ExportController extends Controller
{
    use RespondsWithApi;

    public function index()
    {
        try {
            $customers = Customer::orderBy('name', 'asc')->get();

            $productCatalogs = ProductCatalog::withCount(['products' => function ($query) {
                $query->where('status', 1);
            }])
            ->orderBy('product_name', 'asc')
            ->get();

            $recentVouchers = ExportVoucher::orderByDesc('exported_at')
                ->limit(8)
                ->get();

            return view('exports.create', compact('customers', 'productCatalogs', 'recentVouchers'));
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loi hien thi giao dien: ' . $e->getMessage() . ' tai dong ' . $e->getLine(),
            ], 500);
        }
    }

    public function checkSerial(Request $request, $serial_number)
    {
        try {
            $productId = $request->query('product_id');

            $item = Product::where('serial_number', $serial_number)
                ->where('product_catalog_id', $productId)
                ->where('status', 1)
                ->first();

            if (!$item) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ma Serial khong ton tai trong kho hoac da duoc xuat ban!',
                ], 404);
            }

            $catalog = ProductCatalog::find($productId);

            $payload = [
                'success' => true,
                'data' => [
                    'id' => $item->id,
                    'serial_number' => $item->serial_number,
                ],
            ];

            if ($request->user()?->canViewCostPrices()) {
                $payload['data']['wholesale_price'] = $catalog ? (float) $catalog->wholesale_price : 0;
            }

            return response()->json($payload);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loi kiem tra Serial: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request, ExportStockService $exportStockService)
    {
        $validator = Validator::make($request->all(), [
            'export_type' => 'required|string',
            'customer_type' => 'required|string',
            'buyer_name' => 'required|string',
            'main_items' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Du lieu khong hop le.', $validator->errors(), 422);
        }

        try {
            $result = $exportStockService->export(
                $validator->validated() + $request->all(),
                $request->user()?->id
            );

            return $this->successResponse('Luu don xuat kho thanh cong!', $result);
        } catch (ValidationException $e) {
            return $this->errorResponse('Du lieu xuat kho khong hop le.', $e->errors(), 422);
        } catch (Throwable $e) {
            report($e);

            return $this->errorResponse('Loi xu ly xuat kho: ' . $e->getMessage(), [], 500);
        }
    }

    public function print($id)
    {
        try {
            $voucher = ExportVoucher::findOrFail($id);

            $subVouchers = [];
            if ($voucher->parent_id === null) {
                $subVouchers = ExportVoucher::where('parent_id', $voucher->id)->get();
            }

            return view('exports.print', compact('voucher', 'subVouchers'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', 'Khong tim thay hoa don can in: ' . $e->getMessage());
        }
    }

    public function updateMetadata(Request $request, ExportVoucher $voucher)
    {
        $validated = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:255'],
        ]);

        $voucher->update($validated);

        return redirect()->route('export.index')->with('success', 'Da cap nhat thong tin hoa don.');
    }
}
