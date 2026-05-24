<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->string('public_token')->nullable()->unique()->after('order_code');
            $table->foreignId('prepared_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable()->after('prepared_by');
            $table->timestamp('printed_at')->nullable()->after('prepared_at');
            $table->foreignId('delivered_by')->nullable()->after('printed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable()->after('delivered_by');
            $table->timestamp('failed_at')->nullable()->after('delivered_at');
            $table->text('failure_reason')->nullable()->after('failed_at');
            $table->foreignId('export_voucher_id')->nullable()->after('failure_reason')->constrained('export_vouchers')->nullOnDelete();
        });

        Schema::create('fulfillment_order_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_order_id')->constrained('fulfillment_orders')->cascadeOnDelete();
            $table->foreignId('fulfillment_order_item_id')->nullable()->constrained('fulfillment_order_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('active_product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->foreignId('product_catalog_id')->constrained('product_catalogs')->restrictOnDelete();
            $table->string('serial_number_snapshot');
            $table->string('status')->default('prepared');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->unique('active_product_id');
            $table->index(['fulfillment_order_id', 'status']);
            $table->index('serial_number_snapshot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_order_serials');

        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
            $table->dropConstrainedForeignId('prepared_by');
            $table->dropColumn('prepared_at');
            $table->dropColumn('printed_at');
            $table->dropConstrainedForeignId('delivered_by');
            $table->dropColumn('delivered_at');
            $table->dropColumn('failed_at');
            $table->dropColumn('failure_reason');
            $table->dropConstrainedForeignId('export_voucher_id');
        });
    }
};
