<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('serial_number')->index();
            $table->foreignId('product_catalog_id')->nullable()->constrained('product_catalogs')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->string('movement_type')->index();
            $table->integer('from_status')->nullable();
            $table->integer('to_status')->nullable();
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('import_voucher_id')->nullable()->constrained('import_vouchers')->nullOnDelete();
            $table->foreignId('export_voucher_id')->nullable()->constrained('export_vouchers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->text('note')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['movement_type', 'occurred_at']);
            $table->index(['product_id', 'movement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
