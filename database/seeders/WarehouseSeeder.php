<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\ExportVoucher;
use App\Models\ImportVoucher;
use App\Models\Location;
use App\Models\Product;
use App\Models\ProductCatalog;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = User::query()->where('role', User::ROLE_ADMIN)->first();
            $company = CompanyProfile::current();

            $apple = Supplier::query()->create([
                'name' => 'Apple',
                'contact_person' => 'Apple Distributor',
                'phone' => '0900000001',
            ]);
            $samsung = Supplier::query()->create([
                'name' => 'Samsung',
                'contact_person' => 'Samsung Distributor',
                'phone' => '0900000002',
            ]);

            $keA1 = Location::query()->create(['shelf_name' => 'Ke A1']);
            $keB2 = Location::query()->create(['shelf_name' => 'Ke B2']);
            $vip = Location::query()->create(['shelf_name' => 'VIP']);

            $iphone = ProductCatalog::query()->create([
                'supplier_id' => $apple->id,
                'product_name' => 'iPhone 17 Pro Max 1TB',
                'model_prefix' => 'IP17PM1TB',
                'wholesale_price' => 28000000,
                'agency_margin' => 8,
                'profit_margin' => 15,
                'agency_price' => 30240000,
                'retail_price' => 32200000,
            ]);
            $airpods = ProductCatalog::query()->create([
                'supplier_id' => $apple->id,
                'product_name' => 'AirPods Pro 4',
                'model_prefix' => 'APP4',
                'wholesale_price' => 4200000,
                'agency_margin' => 10,
                'profit_margin' => 20,
                'agency_price' => 4620000,
                'retail_price' => 5040000,
            ]);
            $galaxy = ProductCatalog::query()->create([
                'supplier_id' => $samsung->id,
                'product_name' => 'Galaxy S26 Ultra 512GB',
                'model_prefix' => 'S26U512',
                'wholesale_price' => 22000000,
                'agency_margin' => 7,
                'profit_margin' => 14,
                'agency_price' => 23540000,
                'retail_price' => 25080000,
            ]);

            $customer = Customer::query()->create([
                'name' => 'Nguyen Van A',
                'company_name' => 'Cong ty Demo',
                'address' => '123 Nguyen Trai',
                'tax_code' => '0100000000',
                'phone' => '0912345678',
                'type' => 'retail',
            ]);

            $iphoneImport = $this->createImportVoucher(
                'PN-20260524-APPLE',
                $apple->id,
                $iphone->id,
                $vip->id,
                28000000,
                5,
                $admin?->id,
                now()->subDays(5)
            );
            $galaxyImport = $this->createImportVoucher(
                'PN-20260524-SAMSUNG',
                $samsung->id,
                $galaxy->id,
                $keA1->id,
                22000000,
                3,
                $admin?->id,
                now()->subDays(4)
            );

            DB::table('import_voucher_items')->insert([
                [
                    'import_voucher_id' => $iphoneImport->id,
                    'product_catalog_id' => $iphone->id,
                    'location_id' => $vip->id,
                    'quantity' => 5,
                    'unit_cost' => 28000000,
                    'total_cost' => 140000000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'import_voucher_id' => $galaxyImport->id,
                    'product_catalog_id' => $galaxy->id,
                    'location_id' => $keA1->id,
                    'quantity' => 3,
                    'unit_cost' => 22000000,
                    'total_cost' => 66000000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $iphoneProducts = $this->createProducts([
                'IP17PM-TEST-001',
                'IP17PM-TEST-002',
                'IP17PM-TEST-003',
                'IP17PM-TEST-004',
                'IP17PM-TEST-005',
            ], $iphone, $apple, $vip, $iphoneImport, $admin?->id, now()->subDays(5));

            $galaxyProducts = $this->createProducts([
                'S26U-TEST-001',
                'S26U-TEST-002',
                'S26U-TEST-003',
            ], $galaxy, $samsung, $keA1, $galaxyImport, $admin?->id, now()->subDays(4));

            $exportedAt = now()->subDays(2);
            $exportVoucher = ExportVoucher::query()->create([
                'parent_id' => null,
                'export_code' => 'PX-20260524-0001',
                'export_type' => 'normal',
                'customer_type' => 'retail',
                'seller_name' => $company?->company_name ?: CompanyProfile::fallbackName(),
                'seller_tax_code' => $company?->tax_code,
                'seller_address' => $company?->address,
                'seller_phone' => $company?->hotline,
                'seller_bank_account' => $company?->bank_account,
                'seller_bank_name' => $company?->bank_name,
                'customer_id' => $customer->id,
                'buyer_name' => $customer->name,
                'company_name' => $customer->company_name,
                'address' => $customer->address,
                'tax_code' => $customer->tax_code,
                'items' => json_encode([
                    [
                        'product_id' => $iphone->id,
                        'product_name' => $iphone->product_name,
                        'quantity' => 2,
                        'price' => 32200000,
                        'serials' => ['IP17PM-TEST-001', 'IP17PM-TEST-002'],
                    ],
                ], JSON_UNESCAPED_UNICODE),
                'total_cost' => 56000000,
                'total_amount' => 64400000,
                'note' => 'Seed export voucher for WMS v2 verification.',
                'exported_at' => $exportedAt,
            ]);

            $exportItemId = DB::table('export_voucher_items')->insertGetId([
                'export_voucher_id' => $exportVoucher->id,
                'product_catalog_id' => $iphone->id,
                'quantity' => 2,
                'unit_cost' => 28000000,
                'unit_price' => 32200000,
                'total_cost' => 56000000,
                'total_amount' => 64400000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($iphoneProducts->take(2) as $product) {
                DB::table('export_voucher_item_serials')->insert([
                    'export_voucher_item_id' => $exportItemId,
                    'product_id' => $product->id,
                    'serial_number' => $product->serial_number,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                StockMovement::query()->create([
                    'movement_type' => StockMovement::TYPE_EXPORT,
                    'product_id' => $product->id,
                    'serial_number' => $product->serial_number,
                    'product_catalog_id' => $product->product_catalog_id,
                    'supplier_id' => $product->supplier_id,
                    'from_status' => 1,
                    'to_status' => 2,
                    'from_location_id' => $product->location_id,
                    'to_location_id' => null,
                    'export_voucher_id' => $exportVoucher->id,
                    'user_id' => $admin?->id,
                    'quantity' => 1,
                    'occurred_at' => $exportedAt,
                ]);

                $product->update([
                    'status' => 2,
                    'export_voucher_id' => $exportVoucher->id,
                    'exported_at' => $exportedAt,
                    'updated_at' => $exportedAt,
                ]);
            }

            // Keep an unused catalog and mixed stock/catalog data for report and UI testing.
            $airpods->touch();
            $galaxyProducts->first()?->update(['location_id' => $keB2->id]);
        });
    }

    private function createImportVoucher(
        string $code,
        int $supplierId,
        int $catalogId,
        int $locationId,
        float $unitCost,
        int $quantity,
        ?int $userId,
        $importedAt
    ): ImportVoucher {
        return ImportVoucher::query()->create([
            'import_code' => $code,
            'supplier_id' => $supplierId,
            'product_catalog_id' => $catalogId,
            'location_id' => $locationId,
            'wholesale_price' => $unitCost,
            'total_quantity' => $quantity,
            'total_cost' => $unitCost * $quantity,
            'user_id' => $userId,
            'note' => 'Seed import voucher for WMS v2 verification.',
            'imported_at' => $importedAt,
        ]);
    }

    private function createProducts(array $serials, ProductCatalog $catalog, Supplier $supplier, Location $location, ImportVoucher $voucher, ?int $userId, $importedAt)
    {
        return collect($serials)->map(function (string $serial) use ($catalog, $supplier, $location, $voucher, $userId, $importedAt) {
            $product = Product::query()->create([
                'product_catalog_id' => $catalog->id,
                'supplier_id' => $supplier->id,
                'location_id' => $location->id,
                'serial_number' => $serial,
                'status' => 1,
                'import_voucher_id' => $voucher->id,
                'imported_at' => $importedAt,
                'created_at' => $importedAt,
                'updated_at' => $importedAt,
            ]);

            StockMovement::query()->create([
                'movement_type' => StockMovement::TYPE_IMPORT,
                'product_id' => $product->id,
                'serial_number' => $product->serial_number,
                'product_catalog_id' => $product->product_catalog_id,
                'supplier_id' => $product->supplier_id,
                'from_status' => null,
                'to_status' => 1,
                'from_location_id' => null,
                'to_location_id' => $product->location_id,
                'import_voucher_id' => $voucher->id,
                'user_id' => $userId,
                'quantity' => 1,
                'occurred_at' => $importedAt,
                'created_at' => $importedAt,
                'updated_at' => $importedAt,
            ]);

            return $product;
        });
    }
}
