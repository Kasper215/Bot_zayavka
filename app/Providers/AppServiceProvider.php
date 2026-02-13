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
        if($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
        // Force https if using ngrok (simple check)
        if (str_contains(config('app.url'), 'ngrok') || request()->header('X-Forwarded-Proto') === 'https') {
             \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
