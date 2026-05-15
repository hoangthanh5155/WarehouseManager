<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_catalogs', function (Blueprint $table) {
            // Thêm cột lưu % đại lý (mặc định 0)
            if (!Schema::hasColumn('product_catalogs', 'agency_margin')) {
                $table->decimal('agency_margin', 5, 2)->default(0)->after('wholesale_price');
            }

            // Thêm cột lưu % khách lẻ (mặc định 0)
            if (!Schema::hasColumn('product_catalogs', 'profit_margin')) {
                $table->decimal('profit_margin', 5, 2)->default(0)->after('agency_margin');
            }

            // Thêm cột lưu Giá Đại Lý thực tế (mặc định 0)
            if (!Schema::hasColumn('product_catalogs', 'agency_price')) {
                $table->decimal('agency_price', 15, 2)->default(0)->after('profit_margin');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_catalogs', function (Blueprint $table) {
            $table->dropColumn(['agency_margin', 'profit_margin', 'agency_price']);
        });
    }
};