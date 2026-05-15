<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Location;
use App\Models\ProductCatalog;

class WarehouseSeeder extends Seeder
{
    public function run()
    {
        // 1. Tạo Nhà cung cấp mẫu
        $ncc = Supplier::create(['name' => 'Samsung']);
        $ncc2 = Supplier::create(['name' => 'Apple']);

        // 2. Tạo Vị trí kệ mẫu
        Location::create(['shelf_name' => 'Kệ A1']);
        Location::create(['shelf_name' => 'Kệ B2']);

        // 3. Tạo Danh mục sản phẩm mẫu theo NCC
        ProductCatalog::create([
            'supplier_id' => $ncc->id,
            'product_name' => 'Galaxy S26 Ultra'
        ]);

        ProductCatalog::create([
            'supplier_id' => $ncc2->id,
            'product_name' => 'iPhone 17 Pro Max'
        ]);
    }
} // <--- Phải có dấu này để đóng class