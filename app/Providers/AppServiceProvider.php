<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Fix for OpenSSL on Windows (Laragon) for Web Push
        if (env('OPENSSL_CONF')) {
            putenv('OPENSSL_CONF=' . env('OPENSSL_CONF'));
        } else {
            // Запасной вариант, если в .env пусто
            $opensslPath = 'C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/extras/ssl/openssl.cnf';
            if (file_exists($opensslPath)) {
                putenv('OPENSSL_CONF=' . $opensslPath);
            }
        }

        // Force HTTPS and Root URL if accessing through ngrok
        if (str_contains(request()->header('X-Forwarded-Host') ?? '', 'ngrok') || 
            str_contains(request()->getHost(), 'ngrok') ||
            str_contains(config('app.url'), 'ngrok')) {
            
            URL::forceScheme('https');
            if (str_contains(config('app.url'), 'ngrok')) {
                URL::forceRootUrl(config('app.url'));
            }
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
