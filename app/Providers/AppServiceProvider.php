<?php

namespace App\Providers;

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator; // Thêm thư viện Paginator

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ép Laravel sử dụng giao diện phân trang của Bootstrap 5
        Paginator::useBootstrapFive();

        View::composer('layouts.admin', function ($view) {
            $companyProfileData = null;

            try {
                $companyProfileData = Cache::remember('company_profile.current.v2', now()->addMinutes(10), function () {
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
                $companyProfileData = null;
            }

            $companyProfile = $companyProfileData ? (object) $companyProfileData : null;

            $view->with('currentCompanyProfile', $companyProfile);
            $view->with('systemBrandName', $companyProfileData['company_name'] ?? CompanyProfile::fallbackName());
        });
    }
}
