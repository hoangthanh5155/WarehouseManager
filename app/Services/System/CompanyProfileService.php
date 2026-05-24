<?php

namespace App\Services\System;

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class CompanyProfileService
{
    public function sharedViewData(): array
    {
        $profileData = $this->currentProfileData();

        return [
            'currentCompanyProfile' => $profileData ? (object) $profileData : null,
            'systemBrandName' => $profileData['company_name'] ?? CompanyProfile::fallbackName(),
        ];
    }

    public function currentProfileData(): ?array
    {
        try {
            return Cache::remember('company_profile.current.v2', now()->addMinutes(10), function () {
                if (!Schema::hasTable('company_profiles')) {
                    return null;
                }

                $profile = CompanyProfile::current();

                return $profile ? [
                    'company_name' => $profile->company_name,
                    'tax_code' => $profile->tax_code,
                    'hotline' => $profile->hotline,
                    'address' => $profile->address,
                    'bank_account' => $profile->bank_account,
                    'bank_name' => $profile->bank_name,
                ] : null;
            });
        } catch (\Throwable) {
            return null;
        }
    }
}
