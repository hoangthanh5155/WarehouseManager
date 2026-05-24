<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InternalAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'admin@warehouse.local'],
            [
                'name' => 'admin',
                'display_name' => 'Admin Chủ kho',
                'password' => 'Admin@123456',
                'role' => User::ROLE_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'must_change_password' => false,
            ]
        );

        User::query()->firstOrCreate(
            ['email' => 'accountant@example.com'],
            [
                'name' => 'accountant',
                'display_name' => 'Kế toán',
                'password' => 'password',
                'role' => User::ROLE_ACCOUNTANT,
                'status' => User::STATUS_ACTIVE,
                'must_change_password' => false,
            ]
        );
    }
}
