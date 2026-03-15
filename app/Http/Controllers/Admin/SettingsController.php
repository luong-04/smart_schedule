<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        // Lấy tất cả cài đặt và chuyển thành mảng ['key' => 'value']
        $settings = Setting::pluck('value', 'key')->all();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        // Loại bỏ token để lặp qua dữ liệu
        $data = $request->except('_token');

        // Các checkbox nếu không check sẽ không gửi lên, ta cần gán giá trị 0
        $checkboxes = ['check_teacher_conflict', 'check_room_conflict'];
        foreach($checkboxes as $cb) {
            if (!$request->has($cb)) {
                $data[$cb] = '0';
            }
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Hệ thống đã cập nhật cấu hình mới nhất!');
    }
}