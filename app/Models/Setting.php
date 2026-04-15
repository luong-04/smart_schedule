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

    /**
     * Prefix cho cache key
     */
    const CACHE_PREFIX = 'setting_';

    /**
     * Helper nhanh để lấy giá trị từ settings.
     * Sử dụng Cache::remember để tránh query DB lặp lại.
     */
    public static function getVal($key, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Xóa cache của một key cụ thể
     */
    public static function clearCache($key)
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    /**
     * Xóa toàn bộ cache settings
     */
    public static function clearAllCache()
    {
        // Lấy tất cả keys và xóa từng cái
        $keys = self::pluck('key');
        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }

    /**
     * Đăng ký Observer tự động
     */
    protected static function booted()
    {
        static::observe(\App\Observers\SettingObserver::class);
    }
}