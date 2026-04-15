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
     * Xóa cache khi một setting được tạo mới
     */
    public function created(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }

    /**
     * Xóa cache khi một setting được cập nhật
     */
    public function updated(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }

    /**
     * Xóa cache khi một setting được lưu (created hoặc updated)
     */
    public function saved(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }

    /**
     * Xóa cache khi một setting bị xóa
     */
    public function deleted(Setting $setting): void
    {
        Setting::clearCache($setting->key);
    }
}
