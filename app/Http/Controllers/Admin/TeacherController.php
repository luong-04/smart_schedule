<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportAssignmentRequest;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Classroom;
use App\Models\Assignment;
use App\Models\SubjectConfiguration;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function index()
    {
        $teachers = Teacher::with(['assignments.classroom', 'assignments.subject'])->get();

        // Pre-load toàn bộ SubjectConfiguration 1 lần thay vì query N+1 trong loop
        $configs = SubjectConfiguration::all()->keyBy(function ($c) {
            return $c->subject_id . '-' . $c->grade . '-' . $c->block;
        });

        foreach ($teachers as $t) {
            $totalAssigned = 0;
            foreach ($t->assignments as $as) {
                // Dùng Classroom::BLOCK_CO_BAN constant thay hardcode 'Cơ bản'
                $key    = $as->subject_id . '-' . ($as->classroom->grade ?? '') . '-' . ($as->classroom->block ?? Classroom::BLOCK_CO_BAN);
                $config = $configs->get($key);
                $totalAssigned += $config ? $config->slots_per_week : 0;
            }
            $t->total_assigned_slots = $totalAssigned;
        }

        $groupedTeachers = $teachers->groupBy('department');
        return view('admin.teachers.index', compact('groupedTeachers'));
    }

    // Xử lý xóa nhiều qua checkbox
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            Teacher::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa các giáo viên được chọn!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 giáo viên!');
    }

    public function create()
    {
        return view('admin.teachers.create', [
            'subjects'   => Subject::all(),
            'classrooms' => Classroom::all(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|unique:teachers,code',
            'department'    => 'required|string',
            'max_slots_week' => 'required|integer|min:1',
        ]);
        Teacher::create($data);
        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên thành công!');
    }

    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.edit', [
            'teacher'    => $teacher->load('assignments.subject', 'assignments.classroom'),
            'subjects'   => Subject::all(),
            'classrooms' => Classroom::all(),
        ]);
    }

    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|unique:teachers,code,' . $teacher->id,
            'department'    => 'required|string',
            'max_slots_week' => 'required|integer|min:1',
        ]);
        $teacher->update($data);
        return redirect()->route('teachers.index')->with('success', 'Đã cập nhật hồ sơ!');
    }

    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return back()->with('success', 'Đã xóa giáo viên!');
    }

    public function import(Request $request)
    {
        $request->validate(['import_data' => 'required|string|max:500000']);
        $teachers = json_decode($request->import_data, true);

        if (!is_array($teachers) || count($teachers) > 500) {
            return back()->with('error', 'Dữ liệu import không hợp lệ hoặc vượt quá 500 dòng!');
        }

        $count = 0;
        foreach ($teachers as $t) {
            if (!empty($t['name']) && !empty($t['code'])) {
                Teacher::updateOrCreate(
                    ['code' => $t['code']],
                    [
                        'name'           => $t['name'],
                        'department'     => $t['department'] ?? 'Chưa phân tổ',
                        'max_slots_week' => $t['max_slots_week'] ?? 17,
                    ]
                );
                $count++;
            }
        }
        return back()->with('success', "🎉 Đã import thành công $count Giáo viên!");
    }

    public function importAssignments(ImportAssignmentRequest $request)
    {
        $data = json_decode($request->import_data, true);

        if (!is_array($data) || count($data) > 1000) {
            return back()->with('error', 'Dữ liệu import không hợp lệ hoặc vượt quá 1000 dòng!');
        }

        // Pre-load toàn bộ bảng để tránh N+1 query trong vòng lặp
        $teacherMap  = Teacher::pluck('id', 'code')->all();
        $classMap    = Classroom::pluck('id', 'name')->all();
        $subjectMap  = Subject::pluck('id', 'name')->all();

        $count  = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            $rowNum     = $index + 1;
            $teacherCode = trim($row['teacher_code'] ?? '');
            $className   = trim($row['class_name']   ?? '');
            $subjectName = trim($row['subject_name'] ?? '');

            $teacherId = $teacherMap[$teacherCode] ?? null;
            $classId   = $classMap[$className]     ?? null;
            $subjectId = $subjectMap[$subjectName]  ?? null;

            if ($teacherId && $classId && $subjectId) {
                Assignment::updateOrCreate([
                    'teacher_id' => $teacherId,
                    'class_id'   => $classId,
                    'subject_id' => $subjectId,
                ]);
                $count++;
            } else {
                // Ghi nhận CHI TIẾT lỗi cho từng dòng
                $reason = [];
                if (!$teacherId) $reason[] = "Mã GV \"{$teacherCode}\" không tồn tại";
                if (!$classId)   $reason[] = "Lớp \"{$className}\" không tồn tại";
                if (!$subjectId) $reason[] = "Môn \"{$subjectName}\" không tồn tại";

                $errors[] = [
                    'row'     => $rowNum,
                    'teacher' => $teacherCode,
                    'class'   => $className,
                    'subject' => $subjectName,
                    'reason'  => implode('; ', $reason),
                ];
            }
        }

        // Nếu có lỗi: trả về mảng lỗi để view hiển thị dạng bảng chi tiết
        if (count($errors) > 0) {
            return back()
                ->with('import_success_count', $count)
                ->with('import_errors', $errors);
        }

        return back()->with('success', "🎉 Đã tự động phân công thành công $count lớp cho giáo viên!");
    }
}