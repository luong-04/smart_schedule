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
        $teachers = Teacher::all();
        return view('admin.classrooms.create', compact('teachers'));
    }

    public function store(Request $request) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'required|integer|in:10,11,12',
            'block' => 'required|string|in:KHTN,KHXH,Cơ bản', 
            'shift' => 'required|in:morning,afternoon',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
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
            'block' => 'required|string|in:KHTN,KHXH,Cơ bản', 
            'shift' => 'required|in:morning,afternoon',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $classroom->update($data);
        return redirect()->route('classrooms.index')->with('success', 'Đã cập nhật lớp!');
    }

    public function destroy(Classroom $classroom) {
        $classroom->delete();
        return redirect()->route('classrooms.index')->with('success', 'Đã xóa lớp!');
    }

    // TÍNH NĂNG MỚI: Xóa nhiều lớp cùng lúc
    public function bulkDelete(Request $request) {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            Classroom::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa thành công ' . count($ids) . ' lớp học!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 lớp học để xóa!');
    }

    public function import(Request $request) {
        $request->validate(['import_data' => 'required|string']);
        $classrooms = json_decode($request->import_data, true);
        $count = 0;
        foreach ($classrooms as $c) {
            if (!empty($c['name']) && !empty($c['grade'])) {
                $shift = (strtolower($c['shift'] ?? '') === 'chiều' || strtolower($c['shift'] ?? '') === 'afternoon') ? 'afternoon' : 'morning';
                
                // Map teacher name to ID
                $teacherId = null;
                if (!empty($c['homeroom_teacher'])) {
                    $teacher = Teacher::where('name', $c['homeroom_teacher'])->first();
                    $teacherId = $teacher ? $teacher->id : null;
                }

                Classroom::updateOrCreate(
                    ['name' => $c['name'], 'grade' => $c['grade']],
                    [
                        'shift' => $shift,
                        'homeroom_teacher_id' => $teacherId,
                        'block' => $c['block'] ?? $c['Tổ hợp'] ?? 'Cơ bản' 
                    ]
                );
                $count++;
            }
        }
        return back()->with('success', "🎉 Đã import thành công $count Lớp học!");
    }
}