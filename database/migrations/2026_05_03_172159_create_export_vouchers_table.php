<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_vouchers', function (Blueprint $table) {
            $table->id();
            
            // 🔗 Liên kết Đơn mở rộng với Đơn chính. NULL = Đơn chính
            $table->unsignedBigInteger('parent_id')->nullable(); 
            
            $table->string('export_code')->unique(); // Mã đơn: PX-YYYYMMDD-XXXX
            $table->string('export_type')->default('normal'); // 'normal' (Xuất thường) hoặc 'system' (Đơn hệ thống)
            $table->string('customer_type')->default('retail'); // 'retail' (Giá lẻ) hoặc 'agency' (Giá đại lý)

            // 🏪 Thông tin bên bán snapshot từ hồ sơ công ty/kho
            $table->string('seller_name')->nullable();
            $table->string('seller_tax_code')->nullable();
            $table->string('seller_address')->nullable();
            $table->string('seller_phone')->nullable();

            // 🙋 Thông tin người mua (Thừa hưởng từ đơn chính nếu là đơn mở rộng)
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('buyer_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_code')->nullable();

            // 📦 DỮ LIỆU HÀNG HÓA GỘP (Lưu toàn bộ: Tên hàng, Số lượng, Giá vốn, Giá bán, danh sách mã SN)
            $table->longText('items')->nullable(); 

            // 💰 Tài chính tổng hợp của riêng đơn đó
            $table->decimal('total_cost', 15, 2)->default(0); // Tổng giá vốn để tính lợi nhuận
            $table->decimal('total_amount', 15, 2)->default(0); // Tổng tiền bán (Doanh thu)
            
            $table->text('note')->nullable();
            $table->timestamp('exported_at'); // Thời gian xuất chính xác phục vụ báo cáo/truy vết
            $table->timestamps();

            // Đặt Index để tăng tốc độ truy vấn
            $table->index('parent_id');
            $table->index('exported_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_vouchers');
    }
};
