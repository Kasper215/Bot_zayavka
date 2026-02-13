<?php

namespace App\Providers;

use App\Classes\BusinessLogic;
use Illuminate\Support\ServiceProvider;

class BusinessLogicServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('businesslogic', function ($app) {
            return new BusinessLogic();
        });
    }

    public function provides()
    {
        return [BusinessLogic::class];
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
