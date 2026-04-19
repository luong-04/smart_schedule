<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    /**
     * Store a newly created assignment in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'class_id'   => 'required|exists:classes,id',
            'subject_id' => 'required|exists:subjects,id',
        ], [
            'class_id.required'   => 'Vui lòng chọn lớp học.',
            'subject_id.required' => 'Vui lòng chọn môn học.',
        ]);

        // Check for duplicates
        $exists = Assignment::where('teacher_id', $data['teacher_id'])
            ->where('class_id', $data['class_id'])
            ->where('subject_id', $data['subject_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Phân công này đã tồn tại!');
        }

        Assignment::create($data);
        return back()->with('success', 'Đã thêm phân công giảng dạy thành công!');
    }

    /**
     * Remove the specified assignment from storage.
     */
    public function destroy($id)
    {
        $assignment = Assignment::findOrFail($id);
        $assignment->delete();

        return back()->with('success', 'Đã hủy phân công!');
    }

    /**
     * Bulk delete assignments.
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            $count = Assignment::whereIn('id', $ids)->count();
            Assignment::whereIn('id', $ids)->delete();
            return back()->with('success', "Đã xóa thành công $count phân công!");
        }
        return back()->with('error', 'Vui lòng chọn ít nhất một phân công để xóa!');
    }
}
