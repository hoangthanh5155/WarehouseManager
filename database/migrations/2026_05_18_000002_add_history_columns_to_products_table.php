<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'import_voucher_id')) {
                $table->foreignId('import_voucher_id')->nullable()->after('status')->constrained('import_vouchers')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('import_voucher_id')->index();
            }
            if (!Schema::hasColumn('products', 'export_voucher_id')) {
                $table->foreignId('export_voucher_id')->nullable()->after('imported_at')->constrained('export_vouchers')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'exported_at')) {
                $table->timestamp('exported_at')->nullable()->after('export_voucher_id')->index();
            }
        });

        DB::table('products')
            ->whereNull('imported_at')
            ->update(['imported_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'export_voucher_id')) {
                $table->dropConstrainedForeignId('export_voucher_id');
            }
            if (Schema::hasColumn('products', 'import_voucher_id')) {
                $table->dropConstrainedForeignId('import_voucher_id');
            }
            if (Schema::hasColumn('products', 'exported_at')) {
                $table->dropColumn('exported_at');
            }
            if (Schema::hasColumn('products', 'imported_at')) {
                $table->dropColumn('imported_at');
            }
        });
    }
};
