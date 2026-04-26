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
    /**
     * Danh sách giáo viên kèm theo tổng số tiết dạy đã phân công.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $teachers = Teacher::with(['assignments.classroom', 'assignments.subject'])
            ->orderBy('name', 'asc')
            ->get();

        // Pre-load toàn bộ SubjectConfiguration 1 lần thay vì query N+1 trong loop
        $configs = SubjectConfiguration::all()->keyBy(function ($c) {
            return $c->subject_id . '-' . $c->grade . '-' . $c->block;
        });

        $settings = \App\Models\Setting::pluck('value', 'key')->all();
        $assignFlag    = $settings['assign_gvcn_flag_salute']    ?? 0;
        $assignMeeting = $settings['assign_gvcn_class_meeting']  ?? 0;

        foreach ($teachers as $t) {
            $totalAssigned = 0;
            foreach ($t->assignments as $as) {
                // Dùng Classroom::BLOCK_CO_BAN constant thay hardcode 'Cơ bản'
                $key    = $as->subject_id . '-' . ($as->classroom->grade ?? '') . '-' . ($as->classroom->block ?? Classroom::BLOCK_CO_BAN);
                $config = $configs->get($key);
                $totalAssigned += $config ? $config->slots_per_week : 0;
            }

            // Tính toán tiết định mức cho Giáo viên chủ nhiệm dựa trên thiết lập hệ thống
            $gvcnClassCount = Classroom::where('homeroom_teacher_id', $t->id)->count();
            if ($gvcnClassCount > 0) {
                if ($assignFlag)    $totalAssigned += $gvcnClassCount;
                if ($assignMeeting) $totalAssigned += $gvcnClassCount;
            }

            $t->total_assigned_slots = $totalAssigned;
        }

        $groupedTeachers = $teachers->groupBy('department');
        return view('admin.teachers.index', compact('groupedTeachers'));
    }

    /**
     * Xóa hàng loạt giáo viên qua ID.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if ($ids && is_array($ids)) {
            Teacher::whereIn('id', $ids)->delete();
            return back()->with('success', 'Đã xóa các giáo viên được chọn!');
        }
        return back()->with('error', 'Vui lòng chọn ít nhất 1 giáo viên!');
    }

    /**
     * Hiển thị form tạo giáo viên mới.
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.teachers.create', [
            'subjects'   => Subject::all(),
            'classrooms' => Classroom::all(),
        ]);
    }

    /**
     * Lưu thông tin giáo viên mới vào cơ sở dữ liệu.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|unique:teachers,code',
            'department'    => 'required|string',
            'max_slots_week' => 'required|integer|min:1',
            'off_days'      => 'nullable|array',
            'off_days.*'    => 'integer|min:2|max:7',
        ]);
        
        if (!$request->has('off_days')) {
            $data['off_days'] = null;
        }

        Teacher::create($data);
        return redirect()->route('teachers.index')->with('success', 'Đã thêm giáo viên thành công!');
    }

    /**
     * Hiển thị form chỉnh sửa thông tin giáo viên.
     * 
     * @param Teacher $teacher
     * @return \Illuminate\View\View
     */
    public function edit(Teacher $teacher)
    {
        return view('admin.teachers.edit', [
            'teacher'    => $teacher->load('assignments.subject', 'assignments.classroom'),
            'subjects'   => Subject::all(),
            'classrooms' => Classroom::all(),
        ]);
    }

    /**
     * Cập nhật thông tin giáo viên.
     * 
     * @param Request $request
     * @param Teacher $teacher
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Teacher $teacher)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'code'          => 'required|string|unique:teachers,code,' . $teacher->id,
            'department'    => 'required|string',
            'max_slots_week' => 'required|integer|min:1',
            'off_days'      => 'nullable|array',
            'off_days.*'    => 'integer|min:2|max:7',
        ]);

        if (!$request->has('off_days')) {
            $data['off_days'] = null;
        }

        $teacher->update($data);
        return redirect()->to(route('teachers.index') . '#teacher-' . $teacher->id)
            ->with('success', 'Đã cập nhật hồ sơ giáo viên thành công!');
    }

    /**
     * Xóa một giáo viên khỏi hệ thống.
     * 
     * @param Teacher $teacher
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Teacher $teacher)
    {
        $teacher->delete();
        return back()->with('success', 'Đã xóa giáo viên!');
    }

    /**
     * Nhập dữ liệu giáo viên hàng loạt từ JSON.
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
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

    /**
     * Nhập dữ liệu phân công giảng dạy hàng loạt từ JSON.
     * 
     * @param ImportAssignmentRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function importAssignments(ImportAssignmentRequest $request)
    {
        $data = json_decode($request->import_data, true);

        if (!is_array($data) || count($data) > 1000) {
            return back()->with('error', 'Dữ liệu import không hợp lệ hoặc vượt quá 1000 dòng!');
        }

        // Pre-load data to avoid N+1 queries
        $teacherMap = Teacher::pluck('id', 'code')->all();
        $classMap   = Classroom::pluck('id', 'name')->all();
        $subjectMap = Subject::pluck('id', 'name')->all();

        $count = 0;
        $errors = [];

        foreach ($data as $index => $row) {
            $teacherCode = trim($row['teacher_code'] ?? '');
            $className   = trim($row['class_name']   ?? '');
            $subjectName = trim($row['subject_name'] ?? '');

            $tId = $teacherMap[$teacherCode] ?? null;
            $cId = $classMap[$className]     ?? null;
            $sId = $subjectMap[$subjectName] ?? null;

            if ($tId && $cId && $sId) {
                Assignment::updateOrCreate([
                    'teacher_id' => $tId,
                    'class_id'   => $cId,
                    'subject_id' => $sId,
                ]);
                $count++;
            } else {
                $reason = [];
                if (!$tId) $reason[] = "Mã GV \"$teacherCode\" không tồn tại";
                if (!$cId) $reason[] = "Lớp \"$className\" không tồn tại";
                if (!$sId) $reason[] = "Môn \"$subjectName\" không tồn tại";
                $errors[] = "Dòng " . ($index + 1) . ": " . implode(', ', $reason);
            }
        }

        if (count($errors) > 0) {
            return back()->with('success', "Đã import thành công $count phân công.")
                         ->with('error', "Có các dòng lỗi sau: <br>" . implode('<br>', array_slice($errors, 0, 10)));
        }

        return back()->with('success', "🎉 Đã import thành công $count phân công giảng dạy!");
    }
}