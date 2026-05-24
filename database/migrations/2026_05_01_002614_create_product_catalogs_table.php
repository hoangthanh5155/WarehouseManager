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
    Schema::create('product_catalogs', function (Blueprint $table) {
        $table->id();
        // Liên kết với bảng suppliers
        $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
        $table->string('product_name'); // VD: Galaxy S26 Ultra
        $table->string('model_prefix')->unique(); // VD: S26U (Dùng để sinh mã vạch)
        $table->decimal('wholesale_price', 15, 2)->default(0);
        $table->decimal('agency_margin', 5, 2)->default(0);
        $table->decimal('profit_margin', 5, 2)->default(0);
        $table->decimal('agency_price', 15, 2)->default(0);
        $table->decimal('retail_price', 15, 2)->default(0);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_catalogs');
    }
};
