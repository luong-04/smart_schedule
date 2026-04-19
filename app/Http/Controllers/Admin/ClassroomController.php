<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Teacher;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    /**
     * Danh sách lớp học kèm theo thông tin giáo viên chủ nhiệm.
     * 
     * @return \Illuminate\View\View
     */
    public function index() {
        $classrooms = Classroom::with('homeroomTeacher')
            ->orderBy('grade', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        return view('admin.classrooms.index', compact('classrooms'));
    }

    /**
     * Hiển thị form tạo lớp học mới.
     * 
     * @return \Illuminate\View\View
     */
    public function create() {
        $teachers = Teacher::all();
        return view('admin.classrooms.create', compact('teachers'));
    }

    /**
     * Lưu thông tin lớp học mới vào cơ sở dữ liệu.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Hiển thị form chỉnh sửa thông tin lớp học.
     * 
     * @param Classroom $classroom
     * @return \Illuminate\View\View
     */
    public function edit(Classroom $classroom) {
        $teachers = Teacher::all();
        return view('admin.classrooms.edit', compact('classroom', 'teachers'));
    }

    /**
     * Cập nhật thông tin lớp học.
     * 
     * @param Request $request
     * @param Classroom $classroom
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Classroom $classroom) {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'grade' => 'required|integer|in:10,11,12',
            'block' => 'required|string|in:KHTN,KHXH,Cơ bản', 
            'shift' => 'required|in:morning,afternoon',
            'homeroom_teacher_id' => 'nullable|exists:teachers,id',
        ]);

        $classroom->update($data);
        return redirect()->to(route('classrooms.index') . '#class-' . $classroom->id)
            ->with('success', 'Đã cập nhật thông tin lớp học!');
    }

    /**
     * Xóa một lớp học khỏi hệ thống.
     * 
     * @param Classroom $classroom
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Classroom $classroom) {
        $classroom->delete();
        return redirect()->route('classrooms.index')->with('success', 'Đã xóa lớp!');
    }

    /**
     * Xóa hàng loạt lớp học qua ID.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDelete(Request $request) {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            Classroom::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa thành công ' . count($ids) . ' lớp học!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 lớp học để xóa!');
    }

    /**
     * Nhập dữ liệu lớp học hàng loạt từ JSON.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request) {
        $request->validate(['import_data' => 'required|string']);
        $classrooms = json_decode($request->import_data, true);
        
        // Tối ưu N+1: Load bản đồ giáo viên một lần duy nhất
        $teacherMap = Teacher::pluck('id', 'name')->all();
        
        $count = 0;
        foreach ($classrooms as $c) {
            if (!empty($c['name']) && !empty($c['grade'])) {
                $shift = (strtolower($c['shift'] ?? '') === 'chiều' || strtolower($c['shift'] ?? '') === 'afternoon') ? 'afternoon' : 'morning';
                
                $teacherId = null;
                if (!empty($c['homeroom_teacher'])) {
                    // O(1) Lookup từ bộ nhớ
                    $teacherId = $teacherMap[trim($c['homeroom_teacher'])] ?? null;
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