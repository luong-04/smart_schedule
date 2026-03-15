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
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            // 1. Định nghĩa các giá trị mặc định
            $defaults = [
                'school_name' => 'SMART SCHEDULE THPT',
                'school_year' => '2024 - 2025',
                'fixed_monday_type' => 'null',
                'fixed_saturday_type' => 'null',
                'check_teacher_conflict' => '0',
                'check_room_conflict' => '0',
                'max_consecutive_slots' => '3'
            ];
    
            // 2. Lấy dữ liệu từ DB
            $dbSettings = \App\Models\Setting::pluck('value', 'key')->all();
    
            // 3. Gộp dữ liệu DB đè lên mặc định
            $globalSettings = array_merge($defaults, $dbSettings);
    
            \Illuminate\Support\Facades\View::share('globalSettings', $globalSettings);
        }
    }
}
