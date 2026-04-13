<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Assignment;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index() {
        // Lấy danh sách giáo viên kèm số lượng lớp đang dạy
        $teachers = Teacher::withCount('assignments')->get();
        return view('admin.teachers.index', compact('teachers'));
    }

    public function create() {
        $subjects = Subject::all();
        $classrooms = Classroom::all();
        return view('admin.teachers.create', compact('subjects', 'classrooms'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:teachers,code',
            'max_slots_week' => 'required|integer|min:1',
            'off_days' => 'nullable|array',
        ]);

        // 1. Tạo giáo viên
        $teacher = Teacher::create($data);

        // 2. Nếu có chọn lớp & môn ngay lúc tạo, thực hiện phân công luôn
        if ($request->filled('class_id') && $request->filled('subject_id')) {
            Assignment::create([
                'teacher_id' => $teacher->id,
                'class_id' => $request->class_id,
                'subject_id' => $request->subject_id,
            ]);
        }

        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên và thực hiện phân công!');
    }

    public function edit(Teacher $teacher)
    {
        $subjects = Subject::all();
        $classrooms = Classroom::all();
        $teacher->load('assignments.subject', 'assignments.classroom');
        return view('admin.teachers.edit', compact('teacher', 'subjects', 'classrooms'));
    }

    public function update(Request $request, Teacher $teacher) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:teachers,code,' . $teacher->id,
            'max_slots_week' => 'required|integer|min:1',
            'off_days' => 'nullable|array', // Cập nhật lịch nghỉ
        ]);

        $teacher->update($data);
        return redirect()->route('teachers.index')->with('success', 'Đã cập nhật hồ sơ giáo viên!');
    }

    public function destroy(Teacher $teacher) {
        $teacher->delete();
        return back()->with('success', 'Đã xóa giáo viên!');
    }
    public function import(Request $request) {
        $request->validate(['import_data' => 'required|string']);
        $teachers = json_decode($request->import_data, true);
        $count = 0;
        foreach ($teachers as $t) {
            if (!empty($t['name']) && !empty($t['code'])) {
                Teacher::updateOrCreate(
                    ['code' => $t['code']], // Dùng Mã GV làm gốc, nếu trùng sẽ cập nhật
                    [
                        'name' => $t['name'],
                        'max_slots_week' => $t['max_slots_week'] ?? 15,
                    ]
                );
                $count++;
            }
        }
        return back()->with('success', "🎉 Đã import thành công $count Giáo viên!");
    }
}