<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('viewer')->after('password')->index();
            $table->string('status')->default('active')->after('role')->index();
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->text('last_user_agent')->nullable()->after('last_login_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'display_name',
                'phone',
                'role',
                'status',
                'last_login_at',
                'last_login_ip',
                'last_user_agent',
            ]);
        });
    }
};
