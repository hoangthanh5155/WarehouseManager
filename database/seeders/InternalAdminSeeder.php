<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InternalAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->where('role', User::ROLE_ADMIN)->exists()) {
            return;
        }

        User::query()->create([
            'name' => 'admin',
            'display_name' => 'Admin Chủ kho',
            'email' => 'admin@warehouse.local',
            'password' => 'Admin@123456',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'must_change_password' => false,
        ]);
    }
}
