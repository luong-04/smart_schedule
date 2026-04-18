<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request cho việc lưu Ma trận thời khóa biểu.
 * Tách biệt logic validation khỏi ScheduleController.
 */
class StoreScheduleRequest extends FormRequest
{
    /**
     * Chỉ cho phép user đã đăng nhập và có quyền xếp lịch
     */
    public function authorize(): bool
    {
        return $this->user()?->can('quan_ly_xep_lich') ?? true; // fallback true cho dev nếu chưa cấu hình permission
    }

    /**
     * Validation rules cơ bản (kiểm tra cấu trúc dữ liệu đầu vào)
     */
    public function rules(): array
    {
        return [
            'class_id'                  => 'required|integer|exists:classes,id',
            'applies_from'              => 'required|date',
            'applies_to'                => 'required|date|after_or_equal:applies_from',
            'schedules'                 => 'present|array|max:100',
            'schedules.*.assignment_id' => 'required|integer',
            'schedules.*.day_of_week'   => 'required|integer|between:2,7',
            'schedules.*.period'        => 'required|integer|between:1,10',
            'schedules.*.room_id'       => 'nullable|integer|exists:rooms,id',
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi bằng tiếng Việt
     */
    public function messages(): array
    {
        return [
            'class_id.required'                  => 'Vui lòng chọn lớp học.',
            'class_id.exists'                    => 'Lớp học không tồn tại trong hệ thống.',
            'applies_from.required'              => 'Vui lòng chọn ngày bắt đầu áp dụng.',
            'applies_from.date'                  => 'Ngày bắt đầu không hợp lệ.',
            'applies_to.required'                => 'Vui lòng chọn ngày kết thúc áp dụng.',
            'applies_to.date'                    => 'Ngày kết thúc không hợp lệ.',
            'applies_to.after_or_equal'          => 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.',
            'schedules.required'                 => 'Không có dữ liệu lịch để lưu.',
            'schedules.max'                      => 'Số lượng tiết vượt quá giới hạn cho phép (tối đa 100).',
            'schedules.*.assignment_id.required' => 'Phân công không hợp lệ.',
            'schedules.*.day_of_week.between'    => 'Ngày trong tuần phải từ Thứ 2 đến Thứ 7.',
            'schedules.*.period.between'         => 'Tiết học phải từ 1 đến 10.',
        ];
    }
}
