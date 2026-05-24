<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_voucher_id')->constrained('import_vouchers')->cascadeOnDelete();
            $table->foreignId('product_catalog_id')->constrained('product_catalogs')->restrictOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['import_voucher_id', 'product_catalog_id']);
        });

        Schema::create('export_voucher_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_voucher_id')->constrained('export_vouchers')->cascadeOnDelete();
            $table->foreignId('product_catalog_id')->constrained('product_catalogs')->restrictOnDelete();
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['export_voucher_id', 'product_catalog_id']);
        });

        Schema::create('export_voucher_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('export_voucher_item_id')->constrained('export_voucher_items')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('serial_number');
            $table->timestamps();

            $table->unique('product_id');
            $table->unique(['export_voucher_item_id', 'serial_number'], 'export_item_serial_unique');
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_voucher_item_serials');
        Schema::dropIfExists('export_voucher_items');
        Schema::dropIfExists('import_voucher_items');
    }
};
