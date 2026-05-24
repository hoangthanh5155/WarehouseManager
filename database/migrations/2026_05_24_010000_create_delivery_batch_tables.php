<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('order_type')->default('manual');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->unsignedBigInteger('store_id')->nullable();
            $table->string('customer_type')->default('retail');
            $table->string('buyer_name');
            $table->string('company_name')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_code')->nullable();
            $table->string('status')->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'order_type']);
            $table->index('customer_id');
        });

        Schema::create('fulfillment_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fulfillment_order_id')->constrained('fulfillment_orders')->cascadeOnDelete();
            $table->foreignId('product_catalog_id')->constrained('product_catalogs')->restrictOnDelete();
            $table->string('product_name_snapshot');
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['fulfillment_order_id', 'product_catalog_id'], 'fo_items_order_catalog_idx');
        });

        Schema::create('delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code')->unique();
            $table->string('status')->default('draft');
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('delivery_batch_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_batch_id')->constrained('delivery_batches')->cascadeOnDelete();
            $table->foreignId('fulfillment_order_id')->constrained('fulfillment_orders')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['delivery_batch_id', 'fulfillment_order_id'], 'delivery_batch_order_unique');
            $table->index(['delivery_batch_id', 'status']);
        });

        Schema::create('delivery_batch_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_batch_id')->constrained('delivery_batches')->cascadeOnDelete();
            $table->foreignId('delivery_batch_order_id')->nullable()->constrained('delivery_batch_orders')->nullOnDelete();
            $table->foreignId('fulfillment_order_id')->nullable()->constrained('fulfillment_orders')->nullOnDelete();
            $table->foreignId('fulfillment_order_item_id')->nullable()->constrained('fulfillment_order_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('active_product_id')->nullable()->constrained('products')->restrictOnDelete();
            $table->foreignId('product_catalog_id')->constrained('product_catalogs')->restrictOnDelete();
            $table->string('serial_number');
            $table->string('status')->default('reserved');
            $table->timestamp('reserved_at');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('active_product_id');
            $table->index(['delivery_batch_id', 'status']);
            $table->index(['delivery_batch_order_id', 'status']);
            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_batch_serials');
        Schema::dropIfExists('delivery_batch_orders');
        Schema::dropIfExists('delivery_batches');
        Schema::dropIfExists('fulfillment_order_items');
        Schema::dropIfExists('fulfillment_orders');
    }
};
