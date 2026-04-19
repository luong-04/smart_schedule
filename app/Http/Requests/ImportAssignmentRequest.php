<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request cho việc Import Phân công giảng dạy.
 * Tách biệt logic validation khỏi TeacherController.
 */
class ImportAssignmentRequest extends FormRequest
{
    /**
     * Kiểm tra quyền của người dùng khi thực hiện yêu cầu này.
     * 
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user()?->can('quan_ly_giao_vien') ?? false;
    }

    /**
     * Định nghĩa các quy tắc kiểm tra dữ liệu đầu vào.
     * 
     * @return array
     */
    public function rules(): array
    {
        return [
            'import_data' => 'required|string|max:500000',
        ];
    }

    /**
     * Các thông báo lỗi tùy chỉnh cho từng quy tắc.
     * 
     * @return array
     */
    public function messages(): array
    {
        return [
            'import_data.required' => 'Dữ liệu import không được để trống.',
            'import_data.max'      => 'Dữ liệu import vượt quá kích thước cho phép (500KB).',
        ];
    }
}
