<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Teacher;

use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index() {
        $teachers = Teacher::withCount('assignments')->get();
        return view('admin.teachers.index', compact('teachers'));
    }

    public function create() {
        return view('admin.teachers.create');
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:teachers,code',
            'max_slots_week' => 'required|integer|min:1',
        ]);

        Teacher::create($data);
        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên!');
    }

    public function edit(Teacher $teacher)
    {
        $subjects = \App\Models\Subject::all();
        $classrooms = \App\Models\Classroom::all(); // Lấy danh sách lớp để gán phân công
        
        // Nạp sẵn các phân công của giáo viên này kèm thông tin lớp và môn
        $teacher->load('assignments.subject', 'assignments.classroom');

        return view('admin.teachers.edit', compact('teacher', 'subjects', 'classrooms'));
    }

    public function update(Request $request, Teacher $teacher) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:teachers,code,' . $teacher->id,
            'max_slots_week' => 'required|integer|min:1',
        ]);

        $teacher->update($data);
        return redirect()->route('teachers.index')->with('success', 'Đã cập nhật thông tin!');
    }

    public function destroy(Teacher $teacher) {
        $teacher->delete();
        return redirect()->route('teachers.index')->with('success', 'Đã xóa giáo viên!');
    }
}