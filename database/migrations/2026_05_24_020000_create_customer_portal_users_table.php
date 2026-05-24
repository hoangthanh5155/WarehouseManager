<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_portal_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('account_type')->default('retail');
            $table->string('customer_type')->default('retail');
            $table->string('approval_status')->default('approved');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index(['account_type', 'customer_type', 'approval_status'], 'cpu_type_status_idx');
            $table->index('is_active');
        });

        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->foreignId('customer_portal_user_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('customer_portal_users')
                ->nullOnDelete();
            $table->string('phone')->nullable()->after('buyer_name');

            $table->index('customer_portal_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('fulfillment_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_portal_user_id');
            $table->dropColumn('phone');
        });

        Schema::dropIfExists('customer_portal_users');
    }
};
