<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Hiển thị trang cấu hình hệ thống.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Lấy tất cả cài đặt và chuyển thành mảng ['key' => 'value']
        $settings = Setting::pluck('value', 'key')->all();
        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Cập nhật các giá trị cài đặt hệ thống.
     * 
     * @param Request $request Request chứa các giá trị cài đặt mới.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $data = $request->except('_token');

        // Bổ sung 2 biến mới vào mảng checkboxes
        $checkboxes = [
            'check_teacher_conflict', 
            'check_room_conflict', 
            'assign_gvcn_flag_salute', 
            'assign_gvcn_class_meeting'
        ];
        
        foreach($checkboxes as $cb) {
            if (!$request->has($cb)) {
                $data[$cb] = '0';
            }
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Xóa toàn bộ cache settings sau khi cập nhật
        // (SettingObserver cũng xóa từng key, nhưng clearAllCache đảm bảo không bỏ sót)
        Setting::clearAllCache();

        return back()->with('success', 'Hệ thống đã cập nhật cấu hình mới nhất!');
    }
}