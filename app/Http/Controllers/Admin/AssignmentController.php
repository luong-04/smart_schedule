<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    // Lưu phân công mới từ giao diện tích hợp của Giáo viên
    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id' => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
        ]);

        Assignment::create($data);

        // Quay lại trang trước đó (màn hình Edit giáo viên)
        return back()->with('success', 'Đã phân công lớp và môn học thành công!');
    }

    // Xóa phân công trực tiếp tại danh sách trong màn hình Giáo viên
    public function destroy($id)
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->delete();

        return back()->with('success', 'Đã hủy bỏ phân công!');
    }
}