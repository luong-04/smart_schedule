<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request cho việc Import Phân công giảng dạy.
 * Tách biệt logic validation khỏi TeacherController.
 */
class ImportAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quan_ly_giao_vien') ?? false;
    }

    public function rules(): array
    {
        return [
            'import_data' => 'required|string|max:500000',
        ];
    }

    public function messages(): array
    {
        return [
            'import_data.required' => 'Dữ liệu import không được để trống.',
            'import_data.max'      => 'Dữ liệu import vượt quá kích thước cho phép (500KB).',
        ];
    }
}
