<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('tax_code')->nullable();
            $table->string('hotline')->nullable();
            $table->string('address')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
