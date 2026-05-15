<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        // Liên kết đến danh mục sản phẩm (Tên máy)
        $table->foreignId('product_catalog_id')->constrained('product_catalogs')->onDelete('cascade');
        
        // Liên kết đến nhà cung cấp
        $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
        
        // Liên kết đến vị trí kệ
        $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
        
        // Mã Serial Number/Barcode duy nhất cho từng máy
        $table->string('serial_number')->unique(); 
        
        // Trạng thái: 1 = Trong kho, 2 = Đã bán, 3 = Lỗi/Trả hàng
        $table->integer('status')->default(1);
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
