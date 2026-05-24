<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_voucher_items', function (Blueprint $table) {
            if (!Schema::hasColumn('import_voucher_items', 'product_name_snapshot')) {
                $table->string('product_name_snapshot')->nullable()->after('product_catalog_id');
            }
        });

        Schema::table('export_voucher_items', function (Blueprint $table) {
            if (!Schema::hasColumn('export_voucher_items', 'product_name_snapshot')) {
                $table->string('product_name_snapshot')->nullable()->after('product_catalog_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('export_voucher_items', function (Blueprint $table) {
            if (Schema::hasColumn('export_voucher_items', 'product_name_snapshot')) {
                $table->dropColumn('product_name_snapshot');
            }
        });

        Schema::table('import_voucher_items', function (Blueprint $table) {
            if (Schema::hasColumn('import_voucher_items', 'product_name_snapshot')) {
                $table->dropColumn('product_name_snapshot');
            }
        });
    }
};
