<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Force HTTPS and Root URL if accessing through ngrok
        if (str_contains(request()->header('X-Forwarded-Host') ?? '', 'ngrok') || 
            str_contains(request()->getHost(), 'ngrok') ||
            str_contains(config('app.url'), 'ngrok')) {
            
            \URL::forceScheme('https');
            if (str_contains(config('app.url'), 'ngrok')) {
                \URL::forceRootUrl(config('app.url'));
            }
        }

        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
