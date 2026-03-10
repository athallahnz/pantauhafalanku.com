<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

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
        // Deteksi pintar: Hanya paksa HTTPS JIKA diakses lewat Ngrok / server yang pakai HTTPS
        if (request()->header('x-forwarded-proto') === 'https' || app()->environment('production')) {
            URL::forceScheme('https');
        }

        Carbon::setLocale('id');
    }
}
