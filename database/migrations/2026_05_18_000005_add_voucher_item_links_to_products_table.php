<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'import_voucher_item_id')) {
                $table->foreignId('import_voucher_item_id')->nullable()->after('imported_at')->constrained('import_voucher_items')->nullOnDelete();
            }

            if (!Schema::hasColumn('products', 'export_voucher_item_id')) {
                $table->foreignId('export_voucher_item_id')->nullable()->after('exported_at')->constrained('export_voucher_items')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'export_voucher_item_id')) {
                $table->dropConstrainedForeignId('export_voucher_item_id');
            }

            if (Schema::hasColumn('products', 'import_voucher_item_id')) {
                $table->dropConstrainedForeignId('import_voucher_item_id');
            }
        });
    }
};
