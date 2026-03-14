<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Teacher;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    public function index() {
        $classrooms = Classroom::orderBy('grade', 'asc')->get();
        return view('admin.classrooms.index', compact('classrooms'));
    }

    public function create() {
        $teachers = Teacher::all(); // Lấy danh sách giáo viên
        return view('admin.classrooms.create', compact('teachers'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'required|integer|in:10,11,12',
            'shift' => 'required|in:morning,afternoon',
            'homeroom_teacher' => 'nullable|string', // Cho phép null
        ]);

        Classroom::create($data);
        return redirect()->route('classrooms.index')->with('success', 'Đã tạo lớp thành công!');
    }

    public function edit(Classroom $classroom) {
        $teachers = Teacher::all();
        return view('admin.classrooms.edit', compact('classroom', 'teachers'));
    }

    public function update(Request $request, Classroom $classroom) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'required|integer|in:10,11,12',
            'shift' => 'required|in:morning,afternoon',
            'homeroom_teacher' => 'nullable|string', // Cho phép null
        ]);

        $classroom->update($data);
        return redirect()->route('classrooms.index')->with('success', 'Đã cập nhật lớp!');
    }

    public function destroy(Classroom $classroom) {
        $classroom->delete();
        return redirect()->route('classrooms.index')->with('success', 'Đã xóa lớp!');
    }
}