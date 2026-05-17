<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_vouchers', function (Blueprint $table) {
            $table->string('seller_bank_account')->nullable()->after('seller_phone');
            $table->string('seller_bank_name')->nullable()->after('seller_bank_account');
        });
    }

    public function down(): void
    {
        Schema::table('export_vouchers', function (Blueprint $table) {
            $table->dropColumn(['seller_bank_account', 'seller_bank_name']);
        });
    }
};
