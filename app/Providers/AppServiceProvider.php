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
        // Chia sẻ biến $globalSettings cho tất cả các file .blade.php
        view()->composer('*', function ($view) {
            $view->with('globalSettings', \App\Models\Setting::pluck('value', 'key')->all());
        });
    }
}
