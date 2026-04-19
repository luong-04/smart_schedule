<?php

namespace App\Observers;

use App\Models\Setting;

/**
 * Observer tự động làm mới Cache khi Settings thay đổi.
 * Đảm bảo dữ liệu cấu hình luôn được đồng bộ sau khi admin cập nhật.
 */
class SettingObserver
{
    /**
     * Xóa cache khi một setting được tạo mới.
     * 
     * @param Setting $setting Đối tượng cài đặt vừa tạo.
     */
    public function created(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }

    /**
     * Xóa cache khi một setting được cập nhật.
     * 
     * @param Setting $setting Đối tượng cài đặt vừa cập nhật.
     */
    public function updated(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }

    /**
     * Xóa cache khi một setting được lưu (vừa created hoặc vừa updated).
     * 
     * @param Setting $setting Đối tượng cài đặt vừa lưu.
     */
    public function saved(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }

    /**
     * Xóa cache khi một setting bị xóa.
     * 
     * @param Setting $setting Đối tượng cài đặt vừa xóa.
     */
    public function deleted(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }
}
