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

        View::composer('*', function ($view) {
            $companyProfile = null;

            try {
                $companyProfilePayload = Cache::remember('company_profile.current', now()->addMinutes(10), function () {
                    return [
                        'profile' => Schema::hasTable('company_profiles')
                            ? CompanyProfile::current()
                            : null,
                    ];
                });
                $companyProfile = $companyProfilePayload['profile'] ?? null;
            } catch (\Throwable) {
                $companyProfile = null;
            }

            $view->with('currentCompanyProfile', $companyProfile);
            $view->with('systemBrandName', $companyProfile?->company_name ?: CompanyProfile::fallbackName());
        });
    }
}
