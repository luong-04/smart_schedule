<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Thời gian cache settings (giây) — 6 tiếng
     */
    const CACHE_TTL = 21600;
    
    // Hằng số mặc định cho các tiết cố định
    const DEFAULT_FLAG_DAY    = 2;  // Thứ 2
    const DEFAULT_FLAG_PER_M  = 1;  // Tiết 1 sáng
    const DEFAULT_FLAG_PER_A  = 10; // Tiết 10 chiều
    const DEFAULT_MEET_DAY    = 7;  // Thứ 7
    const DEFAULT_MEET_PER_M  = 5;  // Tiết 5 sáng
    const DEFAULT_MEET_PER_A  = 10; // Tiết 10 chiều

    /**
     * Prefix cho cache key
     */
    const CACHE_PREFIX = 'setting_';

    /**
     * Lấy giá trị cài đặt từ cache hoặc database.
     * 
     * @param string $key Khóa của cài đặt.
     * @param mixed $default Giá trị mặc định nếu không tìm thấy.
     * @return mixed Giá trị của cài đặt.
     */
    public static function getVal($key, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Xóa cache của một cài đặt cụ thể dựa trên khóa.
     * 
     * @param string $key Khóa cần xóa cache.
     */
    public static function clearCache($key)
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Xóa toàn bộ cache của tất cả các cài đặt.
     */
    public static function clearAllCache()
    {
        // Lấy tất cả keys từ DB và xóa triệt để khỏi Cache store
        self::select('key')->each(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });
    }

    /**
     * Đăng ký Observer tự động để xóa cache khi dữ liệu thay đổi.
     */
    protected static function booted()
    {
        static::observe(\App\Observers\SettingObserver::class);
    }
}