<?php

namespace App\Providers;

use App\Models\CompanyProfile;
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
                if (Schema::hasTable('company_profiles')) {
                    $companyProfile = CompanyProfile::current();
                }
            } catch (\Throwable) {
                $companyProfile = null;
            }

            $view->with('currentCompanyProfile', $companyProfile);
            $view->with('systemBrandName', $companyProfile?->company_name ?: CompanyProfile::fallbackName());
        });
    }
}
