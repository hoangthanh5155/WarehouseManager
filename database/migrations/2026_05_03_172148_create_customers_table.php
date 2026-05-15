<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Họ tên người mua hàng
            $table->string('company_name')->nullable(); // Tên đơn vị (nếu có)
            $table->string('address')->nullable(); // Địa chỉ nhận hóa đơn
            $table->string('tax_code')->nullable(); // Mã số thuế hoặc Số điện thoại
            $table->string('phone')->nullable(); // Số điện thoại liên hệ
            $table->string('type')->default('retail'); // 'retail' (Khách lẻ) hoặc 'agency' (Đại lý)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};