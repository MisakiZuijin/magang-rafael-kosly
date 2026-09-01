<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;

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
        try {
            $appName = Setting::appName();
            $appLogo = Setting::appLogo();
            $appFavicon = Setting::appFavicon();
        } catch (\Throwable $e) {
            $appName = config('app.name', 'Kosly');
            $appLogo = asset('images/logo.png');
            $appFavicon = asset('images/logo.png');
        }

        View::share('appName', $appName);
        View::share('appLogo', $appLogo);
        View::share('appFavicon', $appFavicon);
    }
}
