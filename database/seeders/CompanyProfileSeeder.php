<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use Illuminate\Database\Seeder;

class CompanyProfileSeeder extends Seeder
{
    public function run(): void
    {
        if (CompanyProfile::query()->exists()) {
            return;
        }

        CompanyProfile::query()->create([
            'company_name' => config('app.name', 'WMS'),
            'tax_code' => null,
            'hotline' => null,
            'address' => null,
            'bank_account' => null,
            'bank_name' => null,
        ]);
    }
}
